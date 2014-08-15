<?php

/**
 * Crontab observer.
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Model_Observer extends Mage_Cron_Model_Observer
{

    CONST XML_PATH_MARK_AS_ERROR = 'system/cron/mark_as_error_after';
    CONST XML_PATH_HISTORY_MAXNO = 'system/cron/maxNoOfSuccessfulTasks';

    /**
     * Process cron queue
     * Generate tasks schedule
     * Cleanup tasks schedule
     *
     * THIS METHOD IS (almost) IDENTICAL WITH EE 1.13 and CE 1.8
     * (but it is here for compatibility reasons with earlier version, because the new version was refactored)
     *
     * @param Varien_Event_Observer $observer
     */
    public function dispatch($observer)
    {

        if (!Mage::getStoreConfigFlag('system/cron/enable')) {
            return;
        }

        $schedules = $this->getPendingSchedules();
        $jobsRoot = Mage::getConfig()->getNode('crontab/jobs');
        $defaultJobsRoot = Mage::getConfig()->getNode('default/crontab/jobs');

        /** @var $schedule Mage_Cron_Model_Schedule */
        foreach ($schedules->getIterator() as $schedule) {
            $jobConfig = $jobsRoot->{$schedule->getJobCode()};
            if (!$jobConfig || !$jobConfig->run) {
                $jobConfig = $defaultJobsRoot->{$schedule->getJobCode()};
                if (!$jobConfig || !$jobConfig->run) {
                    continue;
                }
            }
            $this->_processJob($schedule, $jobConfig);
        }

        $this->generate();
        $this->cleanup();

        // Aoe_Scheduler: additional stuff added
        $this->checkRunningJobs();
    }

    /**
     * Process cron queue for tasks marked as always
     *
     * @param Varien_Event_Observer $observer
     */
    public function dispatchAlways($observer)
    {

        if (!Mage::getStoreConfigFlag('system/cron/enable')) {
            return;
        }

        // Aoe_Scheduler: additional stuff added
        $this->processKillRequests();

        parent::dispatchAlways($observer);
    }

    /**
     * Process cron task
     *
     * @param Mage_Cron_Model_Schedule $schedule
     * @param $jobConfig
     * @param bool $isAlways
     * @return Mage_Cron_Model_Observer
     */
    protected function _processJob($schedule, $jobConfig, $isAlways = false)
    {
        $runConfig = $jobConfig->run;
        if (!$isAlways) {
            $scheduleLifetime = Mage::getStoreConfig(self::XML_PATH_SCHEDULE_LIFETIME) * 60;
            $now = time();
            $time = strtotime($schedule->getScheduledAt());
            if ($time > $now) {
                return;
            }
        }

        $errorStatus = Mage_Cron_Model_Schedule::STATUS_ERROR;
        try {
            if (!$isAlways) {
                if ($time < $now - $scheduleLifetime) {
                    $errorStatus = Mage_Cron_Model_Schedule::STATUS_MISSED;
                    Mage::throwException(Mage::helper('cron')->__('Too late for the schedule.'));
                }
            }

            // Aoe_Scheduler: stuff from the original method was removed and refactored into the schedule module

            /* @var $schedule Aoe_Scheduler_Model_Schedule */
            $schedule->runNow(!$isAlways);

        } catch (Exception $e) {
            $schedule->setStatus($errorStatus)
                ->setMessages($e->__toString());

            // Aoe_Scheduler: additional handling:
            Mage::dispatchEvent('cron_' . $schedule->getJobCode() . '_exception', array('schedule' => $schedule, 'exception' => $e));
            Mage::dispatchEvent('cron_exception', array('schedule' => $schedule, 'exception' => $e));
            Mage::helper('aoe_scheduler')->sendErrorMail($schedule, $e->__toString());
        }
        $schedule->save();

        return $this;
    }

    /**
     * Check running jobs
     *
     * @return void
     */
    public function checkRunningJobs()
    {

        // check the schedules running on this server
        $processManager = Mage::getModel('aoe_scheduler/processManager');
        /* @var $processManager Aoe_Scheduler_Model_ProcessManager */
        foreach ($processManager->getAllRunningSchedules(gethostname()) as $schedule) {
            /* @var $schedule Aoe_Scheduler_Model_Schedule */
            $schedule->isAlive(); // checks pid and updates record
        }

        // fallback (where process cannot be checked or if one of the servers disappeared)
        // if a task wasn't seen for some time it will be marked as error
        // I'm reusing the
        $maxAge = time() - Mage::getStoreConfig(self::XML_PATH_MARK_AS_ERROR) * 60;

        $schedules = Mage::getModel('cron/schedule')->getCollection()
            ->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_RUNNING)
            ->addFieldToFilter('last_seen', array('lt' => strftime('%Y-%m-%d %H:%M:00', $maxAge)))
            ->load();

        foreach ($schedules->getIterator() as $schedule) {
            /* @var $schedule Aoe_Scheduler_Model_Schedule */
            $schedule->markAsDisappeared(sprintf('Host "%s" has not been available for a while now to update the status of this task and the task is not reporting back by itself', $schedule->getHost()));
        }
    }

    public function deleteDuplicates()
    {
        $cron_schedule = Mage::getSingleton('core/resource')->getTableName('cron_schedule');
        $conn = Mage::getSingleton('core/resource')->getConnection('core_read');

        // TODO: Direct sql is not nice. We can do better... :)
        $results = $conn->fetchAll("
			SELECT
				GROUP_CONCAT(schedule_id) AS ids,
				CONCAT(job_code, scheduled_at) AS jobkey,
				count(*) AS qty
			FROM {$cron_schedule}
			WHERE status = '" . Mage_Cron_Model_Schedule::STATUS_PENDING . "'
			GROUP BY jobkey
			HAVING qty > 1;
		");
        foreach ($results as $row) {
            $ids = explode(',', $row['ids']);
            $removeIds = array_slice($ids, 1);
            foreach ($removeIds as $id) {
                Mage::getModel('cron/schedule')->load($id)->delete();
            }
        }
    }

    /**
     * Generate jobs for config information
     * Rewrites the original method to filter deactivated jobs
     *
     * @param   $jobs
     * @param   array $exists
     * @return  Mage_Cron_Model_Observer
     */
    protected function _generateJobs($jobs, $exists)
    {

        $conf = Mage::getStoreConfig('system/cron/disabled_crons');
        $conf = explode(',', $conf);
        foreach ($conf as &$c) {
            $c = trim($c);
        }

        $newJobs = array();
        foreach ($jobs as $code => $config) {
            if (!in_array($code, $conf)) {
                $newJobs[$code] = $config;
            }
        }

        return parent::_generateJobs($newJobs, $exists);
    }

    /**
     * Generate cron schedule.
     * Rewrites the original method to remove duplicates afterwards (that exists because of a bug)
     *
     * @return Mage_Cron_Model_Observer
     */
    public function generate()
    {

        /**
         * check if schedule generation is needed
         */
        $lastRun = Mage::app()->loadCache(self::CACHE_KEY_LAST_SCHEDULE_GENERATE_AT);
        if ($lastRun > time() - Mage::getStoreConfig(self::XML_PATH_SCHEDULE_GENERATE_EVERY) * 60) {
            return $this;
        }

        $startTime = microtime(true);

        $result = parent::generate();

        $this->deleteDuplicates();

        if ($logFile = Mage::getStoreConfig('system/cron/logFile')) {

            $history = Mage::getModel('cron/schedule')->getCollection()
                ->setPageSize(1)
                ->setOrder('scheduled_at', 'desc')
                ->load();

            $newestSchedule = $history->getFirstItem();
            /* @var $newestSchedule Aoe_Scheduler_Model_Schedule */

            $duration = microtime(true) - $startTime;
            Mage::log('Generated schedule. Newest task is scheduled at "' . $newestSchedule->getScheduledAt() . '". (Duration: ' . round($duration, 2) . ' sec)', null, $logFile);
        }

        return $result;
    }

    /**
     * Get job code white list from environment variable
     *
     * @return array
     */
    public function getWhitelist()
    {
        $whitelist = array();
        if (getenv("SCHEDULER_WHITELIST") !== FALSE) {
            $whitelist = explode(',', getenv("SCHEDULER_WHITELIST"));
        }
        return $whitelist;
    }

    /**
     * Get job code black list from environment variable
     *
     * @return array
     */
    public function getBlacklist()
    {
        $blacklist = array();
        if (getenv("SCHEDULER_BLACKLIST") !== FALSE) {
            $blacklist = explode(',', getenv("SCHEDULER_BLACKLIST"));
        }
        return $blacklist;
    }

    /**
     * Get pending schedules
     *
     * @return mixed
     */
    public function getPendingSchedules()
    {
        if (!$this->_pendingSchedules) {
            $this->_pendingSchedules = Mage::getModel('cron/schedule')->getCollection()
                ->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_PENDING)
                ->addFieldToFilter('scheduled_at', array('lt' => strftime('%Y-%m-%d %H:%M:%S', time())));

            $whitelist = $this->getWhitelist();
            if (!empty($whitelist)) {
                $this->_pendingSchedules->addFieldToFilter('job_code', array('in' => $whitelist));
            }

            $blacklist = $this->getBlacklist();
            if (!empty($blacklist)) {
                $this->_pendingSchedules->addFieldToFilter('job_code', array('nin' => $blacklist));
            }

            $this->_pendingSchedules = $this->_pendingSchedules->load();

            // let's do a cleanup and not execute multiple schedule from the same job in a run but mark them as missed
            // this happens if the cron was blocked by another task and jobs keep piling up.

            $tmp = array();
            foreach ($this->_pendingSchedules as $key => $schedule) {
                /* @var $schedule Aoe_Scheduler_Model_Schedule */
                $tmp[$schedule->getJobCode()][$schedule->getScheduledAt()] = array('key' => $key, 'schedule' => $schedule);
            }

            foreach ($tmp as $jobCode => $schedules) {
                ksort($schedules);
                array_pop($schedules); // we remove the newest one
                foreach ($schedules as $data) {
                    /* @var $data array */
                    $this->_pendingSchedules->removeItemByKey($data['key']);
                    $schedule = $data['schedule'];
                    /* @var $schedule Aoe_Scheduler_Model_Schedule */
                    $schedule
                        ->setMessages('Mulitple tasks with the same job code were piling up. Skipping execution of duplicates.')
                        ->setStatus(Mage_Cron_Model_Schedule::STATUS_MISSED)
                        ->save();
                }
            }
        }

        return $this->_pendingSchedules;
    }

    /**
     * Process kill requests
     *
     * @return void
     */
    public function processKillRequests()
    {
        $processManager = Mage::getModel('aoe_scheduler/processManager');
        /* @var $processManager Aoe_Scheduler_Model_ProcessManager */
        foreach ($processManager->getAllKillRequests(gethostname()) as $schedule) {
            /* @var $schedule Aoe_Scheduler_Model_Schedule */
            $schedule->kill();
        }
    }


    /**
     * Get job for task marked as always
     *
     * (Instead of reusing existing one - which results in loosing the history - create a new one every time)
     *
     * @param $jobCode
     * @return bool|Mage_Cron_Model_Schedule
     */
    protected function _getAlwaysJobSchedule($jobCode)
    {

        $processManager = Mage::getModel('aoe_scheduler/processManager');
        /* @var $processManager Aoe_Scheduler_Model_ProcessManager */
        if (!$processManager->isJobCodeRunning($jobCode)) {
            $ts = strftime('%Y-%m-%d %H:%M:00', time());
            $schedule = Mage::getModel('cron/schedule')/* @var $schedule Mage_Cron_Model_Schedule */
                ->setJobCode($jobCode)
                ->setStatus(Mage_Cron_Model_Schedule::STATUS_RUNNING)
                ->setCreatedAt($ts)
                ->setScheduledAt($ts)
                ->save();
            return $schedule;
        }

        return false;
    }


    /**
     * Clean up the history of tasks
     * This override deals with custom states added in Aoe_Scheduler
     *
     * @return Mage_Cron_Model_Observer
     */
    public function cleanup()
    {
        // check if history cleanup is needed
        $lastCleanup = Mage::app()->loadCache(self::CACHE_KEY_LAST_HISTORY_CLEANUP_AT);
        if ($lastCleanup > time() - Mage::getStoreConfig(self::XML_PATH_HISTORY_CLEANUP_EVERY) * 60) {
            return $this;
        }

        $startTime = microtime(true);

        $history = Mage::getModel('cron/schedule')->getCollection()
            ->addFieldToFilter('status', array('in' => array(
                Aoe_Scheduler_Model_Schedule::STATUS_KILLED,
                Aoe_Scheduler_Model_Schedule::STATUS_DISAPPEARED,
                Aoe_Scheduler_Model_Schedule::STATUS_DIDNTDOANYTHING,
            )))
            ->load();

        $historyLifetimes = array(
            Aoe_Scheduler_Model_Schedule::STATUS_KILLED => Mage::getStoreConfig(self::XML_PATH_HISTORY_SUCCESS) * 60,
            Aoe_Scheduler_Model_Schedule::STATUS_DISAPPEARED => Mage::getStoreConfig(self::XML_PATH_HISTORY_FAILURE) * 60,
            Aoe_Scheduler_Model_Schedule::STATUS_DIDNTDOANYTHING => Mage::getStoreConfig(self::XML_PATH_HISTORY_SUCCESS) * 60,
        );

        $now = time();
        foreach ($history->getIterator() as $record) {
            /* @var $record Aoe_Scheduler_Model_Schedule */
            if (strtotime($record->getExecutedAt()) < $now - $historyLifetimes[$record->getStatus()]) {
                $record->delete();
            }
        }

        parent::cleanup();

        // delete successful tasks
        $maxNo = Mage::getStoreConfig(self::XML_PATH_HISTORY_MAXNO);
        if ($maxNo) {
            $history = Mage::getModel('cron/schedule')->getCollection()
                ->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_SUCCESS)
                ->setOrder('finished_at', 'desc')
                ->load();
            $counter = array();
            foreach ($history->getIterator() as $record) {
                /* @var $record Aoe_Scheduler_Model_Schedule */
                $jobCode = $record->getJobCode();
                if (!isset($counter[$jobCode])) {
                    $counter[$jobCode] = 0;
                }
                $counter[$jobCode]++;
                if ($counter[$jobCode] > $maxNo) {
                    $record->delete();
                }
            }
        }

        if ($logFile = Mage::getStoreConfig('system/cron/logFile')) {
            $duration = microtime(true) - $startTime;
            Mage::log('History cleanup (Duration: ' . round($duration, 2) . ' sec)', null, $logFile);
        }

        return $this;
    }

}
