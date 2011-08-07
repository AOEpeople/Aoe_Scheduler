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
	 * @param string Delimiter string to explode with
	 * @param string The string to explode
	 * @param boolean If set, all empty values will be removed in output
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
	 * @param string value
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

}
