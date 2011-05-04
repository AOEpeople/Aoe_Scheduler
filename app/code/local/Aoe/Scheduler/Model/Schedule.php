<?php

class Aoe_Scheduler_Model_Schedule extends Mage_Cron_Model_Schedule {

	protected $_jobConfiguration;


	/**
	 * Run this task now
	 *
	 * @return Aoe_Scheduler_Model_Schedule
	 */
	public function runNow() {
		$modelCallback = $this->getJobConfiguration()->getModel();

		if (!$this->getCreatedAt()) {
			$this->scheduleNow();
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

		if (!$this->tryLockJob()) {
			// another cron started this job intermittently, so skip it
			return $this;
		}
		$this->setExecutedAt(strftime('%Y-%m-%d %H:%M:%S', time()));

	  	$messages = call_user_func_array($callback, array());

        // added by Fabrizio to also save messages when no exception was thrown
        if (!empty($messages)) {
           	if (!is_string($messages)) {
           		$messages = var_export($messages, 1);
           	}
           	$this->setMessages($messages);
        }

		$this->setStatus(Mage_Cron_Model_Schedule::STATUS_SUCCESS)
			->setFinishedAt(strftime('%Y-%m-%d %H:%M:%S', time()));

		return $this;
	}



	/**
	 * Schedule this task to be executed as soon as possible
	 *
	 * @return Aoe_Scheduler_Model_Schedule
	 */
	public function scheduleNow() {
		$this->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING)
			->setCreatedAt(strftime('%Y-%m-%d %H:%M:%S', time()))
			->setScheduledAt(strftime('%Y-%m-%d %H:%M', time()));
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