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
        return $cronExpr;
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

    public function getType()
    {
        if ($this instanceof Aoe_Scheduler_Model_Job_Xml_Default) {
            $type = 'xml_default';
        } elseif ($this instanceof Aoe_Scheduler_Model_Job_Xml_Global) {
            $type = 'xml_global';
        } elseif ($this instanceof Aoe_Scheduler_Model_Job_Db) {
            if ($xmlJob = $this->getXmlJob()) {
                $type = 'db_overlay_' . $xmlJob->getType();
            } else {
                $type = 'db_original';
            }
        } else {
            $type = 'unkown';
        }
        return $type;
    }


}