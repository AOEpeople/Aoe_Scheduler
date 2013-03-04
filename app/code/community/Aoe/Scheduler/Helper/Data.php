<?php

/**
 * Helper
 *
 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
 */
class Aoe_Scheduler_Helper_Data extends Mage_Core_Helper_Abstract {


	/**
	 * Explodes a string and trims all values for whitespace in the ends.
	 * If $onlyNonEmptyValues is set, then all blank ('') values are removed.
	 *
	 * @see t3lib_div::trimExplode() in TYPO3
	 * @param $delim
	 * @param string $string
	 * @param bool $removeEmptyValues If set, all empty values will be removed in output
	 * @return array Exploded values
	 */
	public function trimExplode($delim, $string, $removeEmptyValues=false) {
		$explodedValues = explode($delim, $string);

		$result = array_map('trim', $explodedValues);

		if ($removeEmptyValues) {
			$temp = array();
			foreach ($result as $value) {
				if ($value !== '') {
					$temp[] = $value;
				}
			}
			$result = $temp;
		}

		return $result;
	}



	/**
	 * Decorate status values
	 *
	 * @param $status
	 * @return string
	 */
	public function decorateStatus($status) {
		switch ($status) {
			case Mage_Cron_Model_Schedule::STATUS_SUCCESS:
				$result = '<span class="bar-green"><span>'.$status.'</span></span>';
				break;
			case Mage_Cron_Model_Schedule::STATUS_PENDING:
				$result = '<span class="bar-lightgray"><span>'.$status.'</span></span>';
				break;
			case Mage_Cron_Model_Schedule::STATUS_RUNNING:
				$result = '<span class="bar-yellow"><span>'.$status.'</span></span>';
				break;
			case Mage_Cron_Model_Schedule::STATUS_MISSED:
				$result = '<span class="bar-orange"><span>'.$status.'</span></span>';
				break;
			case Mage_Cron_Model_Schedule::STATUS_ERROR:
				$result = '<span class="bar-red"><span>'.$status.'</span></span>';
				break;
			default:
				$result = $status;
				break;
		}
		return $result;
	}



	/**
	 * Wrapepr for decorateTime to be used a frame_callback to avoid that additional parameters
	 * conflict with the method's optional ones
	 *
	 * @param string $value
	 * @return string
	 */
	public function decorateTimeFrameCallBack($value) {
		return $this->decorateTime($value, false, NULL);
	}



	/**
	 * Decorate time values
	 *
	 * @param string $value
	 * @param bool $echoToday if true "Today" will be added
	 * @param string $dateFormat make sure Y-m-d is in it, if you want to have it replaced
	 * @return string
	 */
	public function decorateTime($value, $echoToday=false, $dateFormat=NULL) {
		if (empty($value) || $value == '0000-00-00 00:00:00') {
			$value = '';
		} else {
			$value = Mage::getModel('core/date')->date($dateFormat, $value);
			$replace = array(
				Mage::getModel('core/date')->date('Y-m-d ', time()) => $echoToday ? Mage::helper('aoe_scheduler')->__('Today') . ', ' : '', // today
				Mage::getModel('core/date')->date('Y-m-d ', strtotime('+1 day')) => Mage::helper('aoe_scheduler')->__('Tomorrow') . ', ',
				Mage::getModel('core/date')->date('Y-m-d ', strtotime('-1 day')) => Mage::helper('aoe_scheduler')->__('Yesterday') . ', ',
			);
			$value = str_replace(array_keys($replace), array_values($replace), $value);
		}
		return $value;
	}


	/**
	 * Get last heartbeat
	 */
	public function getLastHeartbeat() {
		if ($this->isDisabled('aoescheduler_heartbeat')) {
			return false;
		}
		$schedules = Mage::getModel('cron/schedule')->getCollection(); /* @var $schedules Mage_Cron_Model_Mysql4_Schedule_Collection */
		$schedules->getSelect()->limit(1)->order('executed_at DESC');
		$schedules->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_SUCCESS);
		$schedules->addFieldToFilter('job_code', 'aoescheduler_heartbeat');
		$schedules->load();
		if (count($schedules) == 0) {
			return false;
		}
		$executedAt = $schedules->getFirstItem()->getExecutedAt();
		$value = Mage::getModel('core/date')->date(NULL, $executedAt);
		return $value;
	}


	/**
	 * Diff between to times;
	 *
	 * @param $time1
	 * @param $time2
	 * @return int
	 */
	public function dateDiff($time1, $time2=NULL) {
		if (is_null($time2)) {
			$time2 = Mage::getModel('core/date')->date();
		}
		$time1 = strtotime($time1);
		$time2 = strtotime($time2);
		return $time2 - $time1;
	}



	/**
	 * Check if job code is disabled in configuration
	 *
	 * @param $jobCode
	 * @return bool
	 */
	public function isDisabled($jobCode) {
		$disabledJobs = Mage::getStoreConfig('system/cron/disabled_crons');
		$disabledJobs = $this->trimExplode(',', $disabledJobs);
		return in_array($jobCode, $disabledJobs);
	}

}
