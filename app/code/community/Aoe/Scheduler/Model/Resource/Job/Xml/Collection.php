<?php

/**
 * Collection of available tasks xml
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Model_Resource_Job_Xml_Collection extends Varien_Data_Collection
{

    protected $_dataLoaded = false;

    protected $modelClass;
    protected $nodePath;

    public function setModelClass($modelClass)
    {
        $this->modelClass = $modelClass;
    }

    public function setNodePath($nodePath)
    {
        $this->nodePath = $nodePath;
    }


    /**
     * Load data
     *
     * @param bool $printQuery
     * @param bool $logQuery
     * @return Aoe_Scheduler_Model_Collection_Crons
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        if ($this->_dataLoaded) {
            return $this;
        }

        if (empty($this->modelClass)) {
            Mage::throwException('No model class given');
        }
        if (empty($this->nodePath)) {
            Mage::throwException('No node path given');
        }

        foreach ($this->getAllCodes($this->nodePath) as $jobCode) {
            $job = Mage::getModel($this->modelClass); /* @var $job Aoe_Scheduler_Model_Job_Abstract */
            $job->loadByCode($jobCode);
            $this->addItem($job);
        }

        $this->_dataLoaded = true;
        return $this;
    }

    /**
     * Get all available codes
     *
     * @param $path
     * @return array
     */
    protected function getAllCodes($path)
    {
        $codes = array();
        $config = Mage::getConfig()->getNode($path); /* @var $config Mage_Core_Model_Config_Element */
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