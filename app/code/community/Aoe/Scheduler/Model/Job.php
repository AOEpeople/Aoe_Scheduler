<?php

/**
 * @method Aoe_Scheduler_Model_Job setJobCode($jobCode)
 * @method string getJobCode()
 * @method Aoe_Scheduler_Model_Job setName($name)
 * @method Aoe_Scheduler_Model_Job setDescription($description)
 * @method string getDescription()
 * @method Aoe_Scheduler_Model_Job setShortDescription($shortDescription)
 * @method string getShortDescription()
 * @method Aoe_Scheduler_Model_Job setScheduleCronExpr($scheduleCronExpr)
 * @method string getScheduleCronExpr()
 * @method Aoe_Scheduler_Model_Job setScheduleConfigPath($scheduleConfigPath)
 * @method string getScheduleConfigPath()
 * @method Aoe_Scheduler_Model_Job setRunModel($runModel)
 * @method string getRunModel()
 * @method Aoe_Scheduler_Model_Job setParameters($parameters)
 * @method string getParameters()
 * @method Aoe_Scheduler_Model_Job setGroups($groups)
 * @method string getGroups()
 * @method Aoe_Scheduler_Model_Job load($jobCode)
 * @method Aoe_Scheduler_Model_Resource_Job getResource()
 * @method Aoe_Scheduler_Model_Resource_Job_Collection getCollection()
 * @method Aoe_Scheduler_Model_Resource_Job_Collection getResourceCollection()
 */
class Aoe_Scheduler_Model_Job extends Mage_Core_Model_Abstract
{
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'aoe_scheduler_job';

    /**
     * Parameter name in event
     *
     * @var string
     */
    protected $_eventObject = 'job';

    protected function _construct()
    {
        $this->_setResourceModel('aoe_scheduler/job', 'aoe_scheduler/job_collection');
    }

    public function getName()
    {
        $name = $this->getData('name');
        if (empty($name)) {
            $name = $this->getJobCode();
        }
        return $name;
    }

    /**
     * @param bool $flag
     *
     * @return $this
     */
    public function setIsActive($flag)
    {
        return $this->setData('is_active', !in_array($flag, array(false, 'false', 0, '0'), true));
    }

    /**
     * @return bool
     */
    public function getIsActive()
    {
        return !in_array($this->getData('is_active'), array(false, 'false', 0, '0'), true);
    }

    /**
     * @param string[] $jobData
     *
     * @return $this
     */
    public function setXmlJobData(array $jobData)
    {
        return $this->setData('xml_job_data', $jobData);
    }

    /**
     * @return string[]
     */
    public function getXmlJobData()
    {
        $jobData = $this->getData('xml_job_data');
        return (is_array($jobData) ? $jobData : array());
    }

    /**
     * @param string[] $jobData
     *
     * @return $this
     */
    public function setDbJobData(array $jobData)
    {
        return $this->setData('db_job_data', $jobData);
    }

    /**
     * @return string[]
     */
    public function getDbJobData()
    {
        $jobData = $this->getData('db_job_data');
        return (is_array($jobData) ? $jobData : array());
    }

    /**
     * Returns cron expression (and fetches it from configuration if required)
     *
     * @return string
     */
    public function getCronExpression()
    {
        $cronExpr = null;
        if ($this->getScheduleConfigPath()) {
            $cronExpr = Mage::getStoreConfig($this->getScheduleConfigPath(), Mage_Core_Model_Store::ADMIN_CODE);
        }
        if (empty($cronExpr) && $this->getScheduleCronExpr()) {
            $cronExpr = $this->getScheduleCronExpr();
        }
        return trim($cronExpr);
    }

    /**
     * Is always task
     *
     * @return bool
     */
    public function isAlwaysTask()
    {
        return $this->getCronExpression() == 'always';
    }

    public function getCallback()
    {
        $helper = Mage::helper('aoe_scheduler');
        /* @var $helper Aoe_Scheduler_Helper_Data */
        return $helper->getCallBack($this->getRunModel());
    }

    public function canBeScheduled()
    {
        return $this->getIsActive() && $this->getCronExpression() && !$this->isAlwaysTask();
    }

    /**
     * @return bool
     */
    public function isDbOnly()
    {
        $xmlJobData = $this->getXmlJobData();
        $dbJobData = $this->getDbJobData();
        return empty($xmlJobData) && !empty($dbJobData);
    }

    /**
     * @return bool
     */
    public function isXmlOnly()
    {
        $xmlJobData = $this->getXmlJobData();
        $dbJobData = $this->getDbJobData();
        return !empty($xmlJobData) && empty($dbJobData);
    }

    /**
     * @return bool
     */
    public function isOverlay()
    {
        $xmlJobData = $this->getXmlJobData();
        $dbJobData = $this->getDbJobData();
        return !empty($xmlJobData) && !empty($dbJobData);
    }

    /**
     * @return string
     */
    public function getType()
    {
        if ($this->isDbOnly()) {
            return 'db';
        } elseif ($this->isXmlOnly()) {
            return 'xml';
        } else {
            return 'db_xml';
        }
    }
}
