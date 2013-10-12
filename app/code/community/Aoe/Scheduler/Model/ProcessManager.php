<?php

/**
 * Class Aoe_Scheduler_Model_ProcessManager
 *
 * @author Fabrizio Branca
 * @since 2013-10-11
 */
class Aoe_Scheduler_Model_ProcessManager {

    /**
     * Get all schedules running on this server
     *
     * @param string host
     * @return object
     */
    public function getAllRunningSchedules($host=null) {
        $collection = Mage::getModel('cron/schedule')->getCollection();
        $collection->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_RUNNING);
        if (!is_null($host)) {
            $collection->addFieldToFilter('host', $host);
        }
        return $collection;
    }

}