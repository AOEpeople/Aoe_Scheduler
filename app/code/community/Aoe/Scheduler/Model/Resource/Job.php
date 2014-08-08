<?php
/**
 * Job mysql4 resource
 *
 * @author Fabrizio Branca
 * @since 2014-08-07
 */
class Aoe_Scheduler_Model_Resource_Job extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * Initialize resource
     */
    public function _construct()
    {
        $this->_init('aoe_scheduler/job', 'id');
    }

}
