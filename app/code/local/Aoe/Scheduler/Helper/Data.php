<?php

/**
 * Helper
 *
 * @author Fabrizio Branca
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
	public function decorateStatus($value) {
		switch ($value) {
			case Mage_Cron_Model_Schedule::STATUS_SUCCESS:
				$cell = '<span class="grid-severity-notice"><span>'.$value.'</span></span>';
				break;
			case Mage_Cron_Model_Schedule::STATUS_PENDING:
				$cell = '<span class="grid-severity-minor"><span>'.$value.'</span></span>';
				break;
			case Mage_Cron_Model_Schedule::STATUS_RUNNING:
				$cell = '<span class="grid-severity-major"><span>'.$value.'</span></span>';
				break;
			case Mage_Cron_Model_Schedule::STATUS_MISSED:
			case Mage_Cron_Model_Schedule::STATUS_ERROR:
				$cell = '<span class="grid-severity-critical"><span>'.$value.'</span></span>';
				break;
			default:
				$cell = $value;
				break;
		}
		return $cell;
	}

	/**
	 * Decorate time values
	 *
	 * @return string
	 */
	public function decorateTime($value) {
		if ($value == '0000-00-00 00:00:00') {
			$value = '';
		} else {
			$value = Mage::getModel('core/date')->date(null, $value);
			$replace = array(
				Mage::getModel('core/date')->date('Y-m-d ', time()) => '', // today
				Mage::getModel('core/date')->date('Y-m-d ', strtotime('+1 day')) => Mage::helper('aoe_scheduler')->__('Tomorrow') . ', ',
				Mage::getModel('core/date')->date('Y-m-d ', strtotime('-1 day')) => Mage::helper('aoe_scheduler')->__('Yesterday') . ', ',
			);
			$value = str_replace(array_keys($replace), array_values($replace), $value);
		}
		return $value;
	}

}
