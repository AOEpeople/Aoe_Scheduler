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
 * @method setParameter($parameter)
 * @method getParameter()
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
}