<?php
/**
 * Abstract xml job
 *
 * @author Fabrizio Branca
 * @since 2014-08-10
 */
class Aoe_Scheduler_Model_Job_Xml_Global extends Aoe_Scheduler_Model_Job_Xml_Abstract
{

    public function getConfigPath()
    {
        return 'crontab/jobs';
    }

}