<?php

/**
 * Process Manager
 *
 * @author Fabrizio Branca
 * @since 2013-10-11
 */
class Aoe_Scheduler_Model_ProcessManager
{

    const XML_PATH_MARK_AS_ERROR = 'system/cron/mark_as_error_after';
    const XML_PATH_MAX_JOB_RUNTIME = 'system/cron/max_job_runtime';

    /**
     * Get all schedules running on this server
     *
     * @param string $host
     * @return object
     */
    public function getAllRunningSchedules($host = null)
    {
        $collection = Mage::getModel('cron/schedule')->getCollection();
        $collection->addFieldToFilter('status', Aoe_Scheduler_Model_Schedule::STATUS_RUNNING);
        if (!is_null($host)) {
            $collection->addFieldToFilter('host', $host);
        }
        return $collection;
    }

    /**
     * Get all schedules marked as to be killed
     *
     * @param string|null $host
     * @return object
     */
    public function getAllKillRequests($host = null)
    {
        $collection = $this->getAllRunningSchedules($host);
        $collection->addFieldToFilter('kill_request', array('lt' => strftime('%Y-%m-%d %H:%M:00', time()+60)));
        return $collection;
    }

    /**
     * Check if there's already a job running with the given code
     *
     * @param string $jobCode
     * @param int $ignoreId
     * @return bool
     */
    public function isJobCodeRunning($jobCode, $ignoreId = null)
    {
        $collection = Mage::getModel('cron/schedule')
            ->getCollection()
            ->addFieldToFilter('status', Aoe_Scheduler_Model_Schedule::STATUS_RUNNING)
            ->addFieldToFilter('job_code', $jobCode);
        if (!is_null($ignoreId)) {
            $collection->addFieldToFilter('schedule_id', array('neq' => $ignoreId));
        }
        foreach ($collection as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Schedule */
            $alive = $schedule->isAlive();
            if ($alive !== false) { // TODO: how do we handle null (= we don't know because might be running on a different server?
                return true;
            }
        }
        return false;
    }


    /**
     * Check running jobs
     *
     * @return void
     */
    public function checkRunningJobs()
    {
        $maxJobRuntime = intval(Mage::getStoreConfig(self::XML_PATH_MAX_JOB_RUNTIME));

        if ($maxJobRuntime) {
            foreach ($this->getAllRunningSchedules(gethostname()) as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Schedule */
                // checking if the job isn't running too long
                if ($schedule->isAlive()) {
                    if ($schedule->getDuration() > $maxJobRuntime * 60 && !$schedule->getKillRequest()) {
                        $schedule->requestKill(null, 'Kill requested because job exceeded the max job runtime of ' . $maxJobRuntime . ' minutes.');
                    }
                }
            }
        }

        // fallback (where process cannot be checked or if one of the servers disappeared)
        // if a task wasn't seen for some time it will be marked as error
        $markAsErrorAfter = intval(Mage::getStoreConfig(self::XML_PATH_MARK_AS_ERROR));
        $maxAge = time() - min($markAsErrorAfter, 5) * 60;
        if ($markAsErrorAfter) {
            $schedules = Mage::getModel('cron/schedule')->getCollection()/* @var $schedules Mage_Cron_Model_Resource_Schedule_Collection */
            ->addFieldToFilter('status', Aoe_Scheduler_Model_Schedule::STATUS_RUNNING)
                ->addFieldToFilter('last_seen', array('lt' => strftime('%Y-%m-%d %H:%M:00', $maxAge)))
                ->load();

            foreach ($schedules as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Schedule */
                // check one more time
                if ($schedule->isAlive() !== true) {
                    $schedule->markAsDisappeared(sprintf('Host "%s" has not been available for a while now to update the status of this task and the task is not reporting back by itself', $schedule->getHost()));
                    $schedule->save();
                }
            }
        }

        // clean up "running"(!?) tasks that have never been seen (for whatever reason) and have been scheduled before maxAge
        // by robinfritze. @see https://github.com/AOEpeople/Aoe_Scheduler/issues/40#issuecomment-67749476
        $schedules = Mage::getModel('cron/schedule')->getCollection() /* @var $schedules Mage_Cron_Model_Resource_Schedule_Collection */
            ->addFieldToFilter('status', Aoe_Scheduler_Model_Schedule::STATUS_RUNNING)
            ->addFieldToFilter('last_seen', array('null' => true))
            ->addFieldToFilter('host', array('null' => true))
            ->addFieldToFilter('pid', array('null' => true))
            ->addFieldToFilter('scheduled_at', array('lt' => strftime('%Y-%m-%d %H:%M:00', $maxAge)))
            ->load();

        foreach ($schedules->getIterator() as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Schedule */
            $schedule->setLastSeen(strftime('%Y-%m-%d %H:%M:%S', time()));
            $schedule->markAsDisappeared(sprintf('Process "%s" (id: %s) cannot be found anymore', $schedule->getJobCode(), $schedule->getId()));
        }

    }

    /**
     * Process kill requests
     *
     * @return void
     */
    public function processKillRequests()
    {
        foreach ($this->getAllKillRequests(gethostname()) as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Schedule */
            $schedule->kill();
        }
    }


    /**
     * Run maintenance
     */
    public function watchdog()
    {
        $this->checkRunningJobs();
        $this->processKillRequests();
    }
}
