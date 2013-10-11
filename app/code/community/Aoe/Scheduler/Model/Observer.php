<?php

/**
 * Crontab observer.
 *
 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
 */
class Aoe_Scheduler_Model_Observer extends Mage_Cron_Model_Observer {

	/**
	 * Process cron queue
	 * Generate tasks schedule
	 * Cleanup tasks schedule
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function dispatch($observer)
	{
		$schedules = $this->getPendingSchedules();
		$scheduleLifetime = Mage::getStoreConfig(self::XML_PATH_SCHEDULE_LIFETIME) * 60;
		$now = time();

        // fetch global cronjob config
        $jobsRoot = Mage::getConfig()->getNode('crontab/jobs');

        // extend cronjob config with the configurable ones
        $jobsRoot->extend(
            Mage::getConfig()->getNode('default/crontab/jobs')
        );

		foreach ($schedules->getIterator() as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Schedule */
			try {
				$errorStatus = Mage_Cron_Model_Schedule::STATUS_ERROR;

				$jobConfig = $jobsRoot->{$schedule->getJobCode()};
				if (!$jobConfig || !$jobConfig->run) {
					Mage::throwException(Mage::helper('cron')->__('No valid configuration found.'));
				}

				$time = strtotime($schedule->getScheduledAt());
				if ($time > $now) {
					continue;
				}

				if ($time < $now - $scheduleLifetime) {
					$errorStatus = Mage_Cron_Model_Schedule::STATUS_MISSED;
					Mage::throwException(Mage::helper('cron')->__('Too late for the schedule.'));
				}

				$schedule->runNow(true);

			} catch (Exception $e) {
				$schedule
                    ->setStatus($errorStatus)
					->setMessages($e->__toString());
				Mage::dispatchEvent('cron_' . $schedule->getJobCode() . '_exception', array('schedule' => $schedule, 'exception' => $e));
                Mage::dispatchEvent('cron_exception', array('schedule' => $schedule, 'exception' => $e));

				Mage::helper('aoe_scheduler')->sendErrorMail($schedule, $e->__toString());

			}

			$schedule->save();
		}

		$this->generate();
		$this->checkRunningJobs();
		$this->cleanup();
	}



	/**
	 * Check running jobs
	 *
	 * @return void
	 */
	public function checkRunningJobs() {

		$maxAge = time() - Mage::getStoreConfig(self::XML_PATH_MAX_RUNNING_TIME) * 60;

		$schedules = Mage::getModel('cron/schedule')->getCollection()
			->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_RUNNING)
			->addFieldToFilter('executed_at', array('lt' => strftime('%Y-%m-%d %H:%M:00', $maxAge)))
			->load();

		foreach ($schedules->getIterator() as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Schedule */
			$schedule
				->setStatus(Mage_Cron_Model_Schedule::STATUS_ERROR)
				->setMessages('Job was running longer than the configured max_running_time')
				->save();
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
	protected function _generateJobs($jobs, $exists) {

		$conf = Mage::getStoreConfig('system/cron/disabled_crons');
		$conf = explode(',', $conf);
		foreach ($conf as &$c) { $c = trim($c); }

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
	public function generate() {
		$result = parent::generate();

		$cron_schedule = Mage::getSingleton('core/resource')->getTableName('cron_schedule');
		$conn = Mage::getSingleton('core/resource')->getConnection('core_read');
		$results = $conn->fetchAll("
			SELECT
				GROUP_CONCAT(schedule_id) AS ids,
				CONCAT(job_code, scheduled_at) AS jobkey,
				count(*) AS qty
			FROM {$cron_schedule}
			WHERE status = 'pending'
			GROUP BY jobkey
			HAVING qty > 1;
		");
		foreach($results as $row) {
			$ids = explode(',', $row['ids']);
			$removeIds = array_slice($ids, 1);
			foreach ($removeIds as $id) {
				Mage::getModel('cron/schedule')->load($id)->delete();
			}
		}

		return $result;
	}

	public function getWhitelist() {
		$whitelist = array();
		if(getenv("SCHEDULER_WHITELIST") !== FALSE ) {
			$whitelist = explode(',',getenv("SCHEDULER_WHITELIST"));
		}
		return $whitelist;
	}

	public function getBlacklist() {
		$blacklist = array();
		if(getenv("SCHEDULER_BLACKLIST") !== FALSE) {
			$blacklist = explode(',',getenv("SCHEDULER_BLACKLIST"));
		}
		return $blacklist;
	}

	public function getPendingSchedules()
	{
		if (!$this->_pendingSchedules) {
			$this->_pendingSchedules = Mage::getModel('cron/schedule')->getCollection()
				->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_PENDING);

			$whitelist = $this->getWhitelist();
			if(!empty($whitelist)) {
				$this->_pendingSchedules->addFieldToFilter('job_code', array('in'=> $whitelist));
			}

			$blacklist = $this->getBlacklist();
			if(!empty($blacklist)) {
				$this->_pendingSchedules->addFieldToFilter('job_code', array('nin'=> $blacklist));
			}

			$this->_pendingSchedules = $this->_pendingSchedules->load();
		}
		return $this->_pendingSchedules;
	}
    
}
