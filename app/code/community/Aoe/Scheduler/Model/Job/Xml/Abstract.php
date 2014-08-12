<?php
/**
 * Abstract xml job
 *
 * @author Fabrizio Branca
 * @since 2014-08-10
 */
abstract class Aoe_Scheduler_Model_Job_Xml_Abstract extends Aoe_Scheduler_Model_Job_Abstract
{


    protected $_idFieldName = 'job_code';

    abstract public function getConfigPath();

    /**
     * Get job xml configuration
     *
     * @param string $jobCode path to configuration
     * @return Mage_Core_Model_Config_Element|false
     */
    protected function getJobXmlConfig($jobCode)
    {
        $xmlConfig = false;
        $config = Mage::getConfig()->getNode($this->getConfigPath());
        if ($config instanceof Mage_Core_Model_Config_Element) {
            $xmlConfig = $config->descend($jobCode);
        }
        return $xmlConfig;
    }



    /**
     * Load configuration object by code
     *
     * @param string $jobCode
     * @return $this
     */
    public function loadByCode($jobCode)
    {

        $xmlConfiguration = $this->getJobXmlConfig($jobCode);

        if ($xmlConfiguration === false) {
            return $this;
            // Mage::throwException(sprintf('Could not find job with code "%s"', $jobCode));
        }

        $this->setJobCode($jobCode);

        $cronExpr = null;
        if ($xmlConfiguration->schedule && $xmlConfiguration->schedule->config_path) {
            $this->setScheduleConfigPath((string)$xmlConfiguration->schedule->config_path);
        }
        if ($xmlConfiguration->schedule && $xmlConfiguration->schedule->cron_expr) {
            $this->setScheduleCronExpr((string)$xmlConfiguration->schedule->cron_expr);
        }
        if ($xmlConfiguration->run && $xmlConfiguration->run->model) {
            $this->setRunModel((string)$xmlConfiguration->run->model);
        }

        $disabledCrons = Mage::helper('aoe_scheduler')->trimExplode(',', Mage::getStoreConfig('system/cron/disabled_crons'), true);
        $this->setIsActive(!in_array($this->getId(), $disabledCrons));

        return $this;
    }

    /**
     * Get collection
     *
     * @return Aoe_Scheduler_Model_Resource_Xml_Collection
     */
    public function getCollection()
    {
        $collection = Mage::getResourceModel('aoe_scheduler/job_xml_collection'); /* @var $collection Aoe_Scheduler_Model_Resource_Xml_Collection */
        $collection->setNodePath($this->getConfigPath());
        $collection->setModelClass(get_class($this));
        return $collection;
    }

}