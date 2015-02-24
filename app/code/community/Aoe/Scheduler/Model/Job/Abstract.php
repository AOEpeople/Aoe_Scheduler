<?php
/**
 * Abstract job
 *
 * @author Fabrizio Branca
 * @since 2014-08-10
 *
 * @method setJobCode($jobCode)
 * @method getJobCode()
 * @method setScheduleCronExpr($scheduleCronExpr)
 * @method getScheduleCronExpr()
 * @method setScheduleConfigPath($scheduleConfigPath)
 * @method getScheduleConfigPath()
 * @method setRunModel($runModel)
 * @method getRunModel()
 * @method setIsActive($isActive)
 * @method getIsActive()
 * @method setParameters($parameters)
 * @method getParameters()
 * @method setGroups($groups)
 * @method getGroups()
 */
abstract class Aoe_Scheduler_Model_Job_Abstract extends Mage_Core_Model_Abstract
{

    /**
     * Initialize resource
     */
    public function _construct()
    {
        $this->setIsActive(true);
        parent::_construct();
    }

    abstract public function loadByCode($jobCode);

    /**
     * Returns cron expression (and fetches it from configuration if required)
     */
    public function getCronExpression()
    {
        $cronExpr = null;
        if ($this->getScheduleConfigPath()) {
            $cronExpr = Mage::getStoreConfig($this->getScheduleConfigPath());
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
        $helper = Mage::helper('aoe_scheduler'); /* @var $helper Aoe_Scheduler_Helper_Data */
        return $helper->getCallBack($this->getRunModel());
    }

    public function canBeScheduled()
    {
        return $this->getIsActive() && $this->getCronExpression() && !$this->isAlwaysTask();
    }

    /**
     * @return Aoe_Scheduler_Model_Job_Abstract|null
     */
    public function getParentJob()
    {
        return null;
    }

    /**
     * @return string
     */
    abstract public function getType();
}
