<?php

/**
 * Crontab observer.
 *
 * @author      Fabrizio Branca <fabrizio.branca@aoemedia.de>
 */
class Aoe_Scheduler_Model_Observer extends Mage_Cron_Model_Observer {

	/**
	 * Process cron queue
	 * Geterate tasks schedule
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

		foreach ($schedules->getIterator() as $schedule) {
			$jobConfig = $jobsRoot->{$schedule->getJobCode()};
			if (!$jobConfig || !$jobConfig->run) {
				continue;
			}

			$runConfig = $jobConfig->run;
			$time = strtotime($schedule->getScheduledAt());
			if ($time > $now) {
				continue;
			}
			try {
				$errorStatus = Mage_Cron_Model_Schedule::STATUS_ERROR;
				$errorMessage = Mage::helper('cron')->__('Unknown error.');

				if ($time < $now - $scheduleLifetime) {
					$errorStatus = Mage_Cron_Model_Schedule::STATUS_MISSED;
					Mage::throwException(Mage::helper('cron')->__('Too late for the schedule.'));
				}

				// TODO: this could be replaced by $schedule->runNow();

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
				$schedule->setExecutedAt(strftime('%Y-%m-%d %H:%M:%S', time()))
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

				$schedule->setStatus(Mage_Cron_Model_Schedule::STATUS_SUCCESS)
					->setFinishedAt(strftime('%Y-%m-%d %H:%M:%S', time()));

			} catch (Exception $e) {
				$schedule->setStatus($errorStatus)
					->setMessages($e->__toString());
			}
			$schedule->save();
		}

		$this->generate();
		$this->cleanup();
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

}
