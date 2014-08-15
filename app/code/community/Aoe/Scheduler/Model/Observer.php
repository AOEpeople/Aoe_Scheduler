<?php

/**
 * Crontab observer.
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Model_Observer /* extends Mage_Cron_Model_Observer */
{

    /**
     * Process cron queue
     * Generate tasks schedule
     * Cleanup tasks schedule
     *
     * @param Varien_Event_Observer $observer
     */
    public function dispatch($observer)
    {
        if (!Mage::getStoreConfigFlag('system/cron/enable')) {
            return;
        }

        $scheduleManager = Mage::getModel('aoe_scheduler/scheduleManager'); /* @var $scheduleManager Aoe_Scheduler_Model_ScheduleManager */

        $schedules = $scheduleManager->getPendingSchedules(); /* @var $schedules Mage_Cron_Model_Resource_Schedule_Collection */
        foreach ($schedules as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Schedule */
            $schedule->process();
        }

        $scheduleManager->generateSchedules();
        $scheduleManager->cleanup();

        $processManager = Mage::getModel('aoe_scheduler/processManager'); /* @var $processManager Aoe_Scheduler_Model_ProcessManager */
        $processManager->checkRunningJobs();
    }

    /**
     * Process cron queue for tasks marked as 'always'
     *
     * @param Varien_Event_Observer $observer
     */
    public function dispatchAlways($observer)
    {
        if (!Mage::getStoreConfigFlag('system/cron/enable')) {
            return;
        }

        $processManager = Mage::getModel('aoe_scheduler/processManager'); /* @var $processManager Aoe_Scheduler_Model_ProcessManager */
        $processManager->processKillRequests();

        $scheduleManager = Mage::getModel('aoe_scheduler/scheduleManager'); /* @var $scheduleManager Aoe_Scheduler_Model_ScheduleManager */

        $allJobs = Mage::getModel('aoe_scheduler/factory')->getAllJobs(); /* @var $allJobs Varien_Data_Collection */
        foreach ($allJobs as $job) { /* @var $job Aoe_Scheduler_Model_Job_Abstract */
            if ($job->isAlwaysTask() && $job->getRunModel()) {
                $schedule = $scheduleManager->getScheduleForAlwaysJob($job->getJobCode());
                if ($schedule !== false) {
                    $schedule->process();
                }
            }
        }
    }

}