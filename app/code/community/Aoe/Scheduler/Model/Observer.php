<?php

/**
 * Crontab observer.
 *
 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
 */
class Aoe_Scheduler_Model_Observer extends Mage_Cron_Model_Observer {


	const XML_PATH_MAX_RUNNING_TIME = 'system/cron/max_running_time';

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
		$jobsRoot = Mage::getConfig()->getNode('crontab/jobs');

		foreach ($schedules->getIterator() as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Schedule */
			try {
				$errorStatus = Mage_Cron_Model_Schedule::STATUS_ERROR;
				$errorMessage = Mage::helper('cron')->__('Unknown error.');

				$jobConfig = $jobsRoot->{$schedule->getJobCode()};
				if (!$jobConfig || !$jobConfig->run) {
					Mage::throwException(Mage::helper('cron')->__('No valid configuration found.'));
				}

				$runConfig = $jobConfig->run;
				$time = strtotime($schedule->getScheduledAt());
				if ($time > $now) {
					continue;
				}

				if ($time < $now - $scheduleLifetime) {
					$errorStatus = Mage_Cron_Model_Schedule::STATUS_MISSED;
					Mage::throwException(Mage::helper('cron')->__('Too late for the schedule.'));
				}

				if ($runConfig->model) {
					if (!preg_match(self::REGEX_RUN_MODEL, (string)$runConfig->model, $run)) {
						Mage::throwException(Mage::helper('cron')->__('Invalid model/method definition, expecting "model/class::method".'));
					}
					if (!($model = Mage::getModel($run[1])) || !method_exists($model, $run[2])) {
						Mage::throwException(Mage::helper('cron')->__('Invalid callback: %s::%s does not exist', $run[1], $run[2]));
					}
					$callback = array($model, $run[2]);
					$arguments = array($schedule);
				}
				if (empty($callback)) {
					Mage::throwException(Mage::helper('cron')->__('No callbacks found'));
				}

				if (!$schedule->tryLockJob()) {
					// another cron started this job intermittently, so skip it
					continue;
				}
				/**
					though running status is set in tryLockJob we must set it here because the object
					was loaded with a pending status and will set it back to pending if we don't set it here
				 */
				$schedule
					->setStatus(Mage_Cron_Model_Schedule::STATUS_RUNNING)
					->setExecutedAt(strftime('%Y-%m-%d %H:%M:%S', time()))
					->save();

				$messages = call_user_func_array($callback, $arguments);

				// added by Fabrizio to also save messages when no exception was thrown
				if (!empty($messages)) {
					if (is_object($messages)) {
						$messages = get_class($messages);
					} elseif (!is_scalar($messages)) {
						$messages = var_export($messages, 1);
					}
					$schedule->setMessages($messages);
				}

				if (strtoupper(substr($messages, 0, 6)) != 'ERROR:') {
					$schedule->setStatus(Mage_Cron_Model_Schedule::STATUS_SUCCESS);
				} else {
					$schedule->setStatus(Mage_Cron_Model_Schedule::STATUS_ERROR);
				}
				$schedule->setFinishedAt(strftime('%Y-%m-%d %H:%M:%S', time()));

			} catch (Exception $e) {
				$schedule->setStatus($errorStatus)
					->setMessages($e->__toString());
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

}
