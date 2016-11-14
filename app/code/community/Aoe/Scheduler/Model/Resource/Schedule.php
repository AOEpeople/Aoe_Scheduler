<?php

class Aoe_Scheduler_Model_Resource_Schedule extends Mage_Cron_Model_Resource_Schedule
{

    /**
     * @param bool $readCommitted
     * @return Mage_Core_Model_Resource_Abstract
     */
    public function beginTransaction($readCommitted = FALSE)
    {
        if ($readCommitted && $this->_getWriteAdapter()->getTransactionLevel() === 0) {
            $this->_getWriteAdapter()->query('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
        }
        return parent::beginTransaction();
    }

}
