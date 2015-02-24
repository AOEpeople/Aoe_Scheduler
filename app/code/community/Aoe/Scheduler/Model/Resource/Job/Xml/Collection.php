<?php

/**
 * Collection of available XML based jobs
 */
class Aoe_Scheduler_Model_Resource_Job_Xml_Collection extends Varien_Data_Collection
{
    protected $_dataLoaded = false;

    /**
     * Load data
     *
     * @param bool $printQuery
     * @param bool $logQuery
     *
     * @return $this
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        if ($this->_dataLoaded) {
            return $this;
        }

        foreach ($this->getAllCodes() as $jobCode) {
            /* @var Aoe_Scheduler_Model_Job_Xml $job */
            $job = Mage::getModel('aoe_scheduler/job_xml');
            $job->loadByCode($jobCode);
            $this->addItem($job);
        }

        $this->_dataLoaded = true;
        return $this;
    }

    /**
     * Get all available codes
     *
     * @return array
     */
    protected function getAllCodes()
    {
        $config = array();
        $xmlConfig = Mage::getConfig()->getNode('crontab/jobs');
        if ($xmlConfig instanceof Mage_Core_Model_Config_Element) {
            $config = $xmlConfig->asArray();
        }
        $xmlConfig = Mage::getConfig()->getNode('default/crontab/jobs');
        if ($xmlConfig instanceof Mage_Core_Model_Config_Element) {
            $config = array_replace_recursive($config, $xmlConfig->asArray());
        }

        $codes = array_keys($config);

        sort($codes);

        return $codes;
    }
}
