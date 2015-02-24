<?php

/**
 * XML config based jobs (from File and DB)
 *
 */
class Aoe_Scheduler_Model_Job_Xml extends Aoe_Scheduler_Model_Job_Abstract
{
    protected $_idFieldName = 'job_code';

    /**
     * Load configuration object by code
     *
     * @param string $jobCode
     *
     * @return $this
     */
    public function loadByCode($jobCode)
    {
        $config = $this->getConfig($jobCode);

        if (empty($config)) {
            return $this;
        }

        $this->setJobCode($jobCode);

        $cronExpr = null;
        if (isset($config['schedule']['config_path'])) {
            $this->setScheduleConfigPath((string)$config['schedule']['config_path']);
        }
        if (isset($config['schedule']['cron_expr'])) {
            $this->setScheduleCronExpr((string)$config['schedule']['cron_expr']);
        }
        if (isset($config['run']['model'])) {
            $this->setRunModel((string)$config['run']['model']);
        }
        if (isset($config['groups'])) {
            $this->setGroups((string)$config['groups']);
        }
        if (isset($config['name'])) {
            $this->setName((string)$config['name']);
        }
        if (isset($config['short_description'])) {
            $this->setShortDescription((string)$config['short_description']);
        }
        if (isset($config['description'])) {
            $this->setDescription((string)$config['description']);
        }

        $disabledCrons = Mage::helper('aoe_scheduler')->trimExplode(',', Mage::getStoreConfig('system/cron/disabled_crons'), true);

        $this->setIsActive(!in_array($this->getId(), $disabledCrons));

        return $this;
    }

    /**
     * Get collection
     *
     * @return Aoe_Scheduler_Model_Resource_Job_Xml_Collection
     */
    public function getCollection()
    {
        return Mage::getResourceModel('aoe_scheduler/job_xml_collection');
    }

    public function getType()
    {
        return 'xml';
    }

    /**
     * Get job xml configuration
     *
     * @param string $jobCode path to configuration
     *
     * @return array
     */
    protected function getConfig($jobCode)
    {
        $config = array();
        $xmlConfig = Mage::getConfig()->getNode('crontab/jobs/' . $jobCode);
        if ($xmlConfig instanceof Mage_Core_Model_Config_Element) {
            $config = $xmlConfig->asArray();
        }
        $xmlConfig = Mage::getConfig()->getNode('default/crontab/jobs/' . $jobCode);
        if ($xmlConfig instanceof Mage_Core_Model_Config_Element) {
            $config = array_replace_recursive($config, $xmlConfig->asArray());
        }
        return $config;
    }
}
