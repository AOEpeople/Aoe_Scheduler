<?php

/**
 * Collection of available tasks (crons)
 *
 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
 */
class Aoe_Scheduler_Model_Collection_Crons extends Varien_Data_Collection {

	protected $_dataLoaded = false;

	/**
	 * Load data
	 *
	 * @param bool $printQuery
	 * @param bool $logQuery
	 * @return Aoe_Scheduler_Model_Collection_Crons
	 */
	public function loadData($printQuery = false, $logQuery = false) {
		if ($this->_dataLoaded) {
			return $this;
		}

		foreach ($this->getAllCodes() as $code) {
			$configuration = Mage::getModel('aoe_scheduler/configuration')->loadByCode($code);
			$this->addItem($configuration);
		}

		$this->_dataLoaded = true;
		return $this;
	}



	/**
	 * Get all available codes
	 *
	 * @return array
	 */
	protected function getAllCodes() {
		$codes = array();
		$config = Mage::getConfig()->getNode('crontab/jobs'); /* @var $config Mage_Core_Model_Config_Element */
		if ($config instanceof Mage_Core_Model_Config_Element) {
			foreach ($config->children() as $key => $tmp) {
				if (!in_array($key, $codes)) {
					$codes[] = $key;
				}
			}
		}
		$config = Mage::getConfig()->getNode('default/crontab/jobs'); /* @var $config Mage_Core_Model_Config_Element */
		if ($config instanceof Mage_Core_Model_Config_Element) {
			foreach ($config->children() as $key => $tmp) {
				if (!in_array($key, $codes)) {
					$codes[] = $key;
				}
			}
		}
		sort($codes);
		return $codes;
	}

}