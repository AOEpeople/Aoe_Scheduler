<?php
/**
 * Class ${NAME}
 *
 * @author Fabrizio Branca
 * @since 2014-08-10
 */
class Aoe_Scheduler_Model_Job_Db extends Aoe_Scheduler_Model_Job_Abstract {

    /**
     * Initialize resource
     */
    public function _construct()
    {
        $this->_init('aoe_scheduler/job_db', 'id');
    }

    public function loadByCode($jobCode)
    {
        $this->load($jobCode, 'job_code');
        return $this;
    }

}