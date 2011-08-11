<?php
/**
 * Collection of global tasks (crons)
 * 
 */
class Aoe_Scheduler_Model_Collection_Globalcrons extends Aoe_Scheduler_Model_Collection_Crons {
	
	protected $_dataLoaded = false;
	
    /**
     * Get all available global codes
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
		sort($codes);
    	return $codes;
    }
    
}