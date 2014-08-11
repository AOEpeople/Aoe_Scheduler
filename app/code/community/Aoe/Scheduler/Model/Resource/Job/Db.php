<?php
/**
 * Job mysql4 resource
 *
 * @author Fabrizio Branca
 * @since 2014-08-07
 */
class Aoe_Scheduler_Model_Resource_Job_Db extends Mage_Core_Model_Resource_Db_Abstract
{

    protected $_isPkAutoIncrement = false;

    /**
     * Initialize resource
     */
    public function _construct()
    {
        $this->_init('aoe_scheduler/job', 'job_code');
    }

}
