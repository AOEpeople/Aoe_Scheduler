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
 */
abstract class Aoe_Scheduler_Model_Job_Abstract extends Mage_Core_Model_Abstract
{

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
     * Get callback from runModel
     * TODO: should this go into the schedule? Or even a helper? Is this required here for validation?
     *
     * @param $runModel
     * @return array
     */
    protected function getCallBack($runModel)
    {
        if (!preg_match(Mage_Cron_Model_Observer::REGEX_RUN_MODEL, (string)$runModel, $run)) {
            Mage::throwException(Mage::helper('cron')->__('Invalid model/method definition, expecting "model/class::method".'));
        }
        if (!($model = Mage::getModel($run[1])) || !method_exists($model, $run[2])) {
            Mage::throwException(Mage::helper('cron')->__('Invalid callback: %s::%s does not exist', $run[1], $run[2]));
        }
        $callback = array($model, $run[2]);
        return $callback;
    }

}