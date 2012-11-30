<?php
/**
 * Scheduler API
 *
 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
 */
class Aoe_Scheduler_Model_Api extends Mage_Api_Model_Resource_Abstract {

	/**
	 * Run task
	 *
	 * @param $code
	 * @return array
	 */
	public function runNow($code) {
		$schedule = Mage::getModel('cron/schedule') /* @var $schedule Aoe_Scheduler_Model_Schedule */
			->setJobCode($code)
			->runNow(false) // without trying to lock the job
			->save();
		return $schedule->getData();
	}

	/**
	 * Schedule task
	 *
	 * @param $code
	 * @param null $time
	 * @return array
	 */
	public function schedule($code, $time=NULL) {
		$schedule = Mage::getModel('cron/schedule') /* @var $schedule Aoe_Scheduler_Model_Schedule */
			->setJobCode($code)
			->schedule($time)
			->save();
		return $schedule->getData();
	}

	/**
	 * Get info
	 *
	 * @param $id
	 * @return string
	 */
	public function info($id) {
		$schedule = Mage::getModel('cron/schedule')->load($id); /* @var $schedule Aoe_Scheduler_Model_Schedule */
		return $schedule->getData();
	}

}
