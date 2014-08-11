<?php

/**
 * Job Collection
 *
 * @author Fabrizio Branca
 * @since 2014-08-07
 */
class Aoe_Scheduler_Model_Resource_Job_Db_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Initialize resource collection
     */
    public function _construct()
    {
        $this->_init('aoe_scheduler/job_db');
    }

}
