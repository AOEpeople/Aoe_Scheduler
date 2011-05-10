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
	 * @param	string		Delimiter string to explode with
	 * @param	string		The string to explode
	 * @param	boolean		If set, all empty values will be removed in output
	 * @param	integer		If positive, the result will contain a maximum of
	 *						 $limit elements, if negative, all components except
	 *						 the last -$limit are returned, if zero (default),
	 *						 the result is not limited at all. Attention though
	 *						 that the use of this parameter can slow down this
	 *						 function.
	 * @return	array		Exploded values
	 */
	public function trimExplode($delim, $string, $removeEmptyValues = FALSE, $limit = 0) {
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

		if ($limit != 0) {
			if ($limit < 0) {
				$result = array_slice($result, 0, $limit);
			} elseif (count($result) > $limit) {
				$lastElements = array_slice($result, $limit - 1);
				$result = array_slice($result, 0, $limit - 1);
				$result[] = implode($delim, $lastElements);
			}
		}

		return $result;
	}
	

}
