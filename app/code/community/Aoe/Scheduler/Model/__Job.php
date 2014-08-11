<?php

/**
 * Cron Job (this is a the job definition, while the schedule is the individual instance of when the job is executed)
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
class Aoe_Scheduler_Model_Job extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        $this->_init('aoe_scheduler/job');

        // default value
        $this->setIsActive(true);
    }

}