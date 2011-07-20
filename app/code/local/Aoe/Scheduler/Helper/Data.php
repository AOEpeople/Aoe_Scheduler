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

}
