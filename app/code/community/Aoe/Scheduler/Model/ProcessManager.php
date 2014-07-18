<?php

/**
 * Class Aoe_Scheduler_Model_ProcessManager
 *
 * @author Fabrizio Branca
 * @since 2013-10-11
 */
class Aoe_Scheduler_Model_ProcessManager
{

    /**
     * Get all schedules running on this server
     *
     * @param string host
     * @return object
     */
    public function getAllRunningSchedules($host = null)
    {
        $collection = Mage::getModel('cron/schedule')->getCollection();
        $collection->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_RUNNING);
        if (!is_null($host)) {
            $collection->addFieldToFilter('host', $host);
        }
        return $collection;
    }

    /**
     * Get all schedules marked as to be killed
     *
     * @param null $host
     * @return object
     */
    public function getAllKillRequests($host = null)
    {
        $collection = $this->getAllRunningSchedules($host);
        $collection->addFieldToFilter('kill_request', array('lt' => strftime('%Y-%m-%d %H:%M:00', time())));
        return $collection;
    }

    /**
     * Check if there's alread a job running with the given code
     *
     * @param string $jobCode
     * @param int $ignoreId
     * @return bool
     */
    public function isJobCodeRunning($jobCode, $ignoreId = NULL)
    {
        $collection = Mage::getModel('cron/schedule')
            ->getCollection()
            ->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_RUNNING)
            ->addFieldToFilter('job_code', $jobCode);
        if (!is_null($ignoreId)) {
            $collection->addFieldToFilter('schedule_id', array('neq' => $ignoreId));
        }
        foreach ($collection as $s) {
            /* @var $s Aoe_Scheduler_Model_Schedule */
            $alive = $s->isAlive();
            if ($alive !== false) { // TODO: how do we handle null (= we don't know because might be running on a different server?
                return true;
            }
        }
        return false;
    }

}
