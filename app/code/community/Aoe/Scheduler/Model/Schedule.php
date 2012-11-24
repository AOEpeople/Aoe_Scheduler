<?php

/**
 * @method string getExecutedAt()
 * @method string getFinishedAt()
 * @method string getStatus()
 * @method string getMessages()
 * @method string getCreatedAt()
 * @method string getScheduledAt()
 * @method string getJobCode()
 */
class Aoe_Scheduler_Model_Schedule extends Mage_Cron_Model_Schedule {

	/**
	 * @var Aoe_Scheduler_Model_Configuration
	 */
	protected $_jobConfiguration;


	/**
	 * Run this task now
	 *
	 * @param bool $tryLockJob
	 * @return Aoe_Scheduler_Model_Schedule
	 */
	public function runNow($tryLockJob=true) {
		$modelCallback = $this->getJobConfiguration()->getModel();

		if (!$this->getCreatedAt()) {
			$this->schedule();
		}

		if (!preg_match(Mage_Cron_Model_Observer::REGEX_RUN_MODEL, $modelCallback, $run)) {
			Mage::throwException(Mage::helper('cron')->__('Invalid model/method definition, expecting "model/class::method".'));
		}
		if (!($model = Mage::getModel($run[1])) || !method_exists($model, $run[2])) {
			Mage::throwException(Mage::helper('cron')->__('Invalid callback: %s::%s does not exist', $run[1], $run[2]));
		}
		$callback = array($model, $run[2]);

		if (empty($callback)) {
			Mage::throwException(Mage::helper('cron')->__('No callbacks found'));
		}

		// lock job requires the record to be saved and having status Mage_Cron_Model_Schedule::STATUS_PENDING
		// workaround could be to do this: $this->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING)->save();
		if ($tryLockJob && !$this->tryLockJob()) {
			// another cron started this job intermittently, so skip it
			return $this;
		}
		$this->setExecutedAt(strftime('%Y-%m-%d %H:%M:%S', time()));

		$messages = call_user_func_array($callback, array($this));

		// added by Fabrizio to also save messages when no exception was thrown
		if (!empty($messages)) {
			if (is_object($messages)) {
				$messages = get_class($messages);
			} elseif (!is_scalar($messages)) {
				$messages = var_export($messages, 1);
			}
			$this->setMessages($messages);
		}

		if (strtoupper(substr($messages, 0, 6)) != 'ERROR:') {
			$this->setStatus(Mage_Cron_Model_Schedule::STATUS_SUCCESS);
		} else {
			$this->setStatus(Mage_Cron_Model_Schedule::STATUS_ERROR);
		}

		$this->setFinishedAt(strftime('%Y-%m-%d %H:%M:%S', time()));

		return $this;
	}



	/**
	 * Schedule this task to be executed as soon as possible
	 *
	 * @deprecated use Aoe_Scheduler_Model_Schedule::schedule() instead
	 * @return Aoe_Scheduler_Model_Schedule
	 */
	public function scheduleNow() {
		return $this->schedule();
	}



	/**
	 * Schedule this task to be executed at a given time
	 *
	 * @param int $time
	 * @return Aoe_Scheduler_Model_Schedule
	 */
	public function schedule($time=NULL) {
		if (is_null($time)) {
			$time = time();
		}
		$this->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING)
			->setCreatedAt(strftime('%Y-%m-%d %H:%M:%S', time()))
			->setScheduledAt(strftime('%Y-%m-%d %H:%M:%S', $time))
			->save();
		return $this;
	}



	/**
	 * Get job configuration
	 *
	 * @return Aoe_Scheduler_Model_Configuration
	 */
	public function getJobConfiguration() {
		if (is_null($this->_jobConfiguration)) {
			$this->_jobConfiguration = Mage::getModel('aoe_scheduler/configuration')->loadByCode($this->getJobCode());
		}
		return $this->_jobConfiguration;
	}



	/**
	 * Get start time (planned or actual)
	 *
	 * @return string
	 */
	public function getStarttime() {
		$starttime = $this->getExecutedAt();
		if (empty($starttime) || $starttime == '0000-00-00 00:00:00') {
			$starttime = $this->getScheduledAt();
		}
		return $starttime;
	}



	/**
	 * Get job duration
	 *
	 * @return bool|int time in seconds, or false
	 */
	public function getDuration() {
		$duration = false;
		if ($this->getExecutedAt() && ($this->getExecutedAt() != '0000-00-00 00:00:00')
			&& $this->getFinishedAt() && ($this->getFinishedAt() != '0000-00-00 00:00:00')) {
			$duration = strtotime($this->getFinishedAt()) - strtotime($this->getExecutedAt());
		}
		return $duration;
	}


}