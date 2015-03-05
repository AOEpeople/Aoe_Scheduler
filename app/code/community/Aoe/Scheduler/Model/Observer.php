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
    public function dispatch(Varien_Event_Observer $observer)
    {
        if (!Mage::getStoreConfigFlag('system/cron/enable')) {
            return;
        }

        $processManager = Mage::getModel('aoe_scheduler/processManager'); /* @var $processManager Aoe_Scheduler_Model_ProcessManager */
        $processManager->watchdog();

        $scheduleManager = Mage::getModel('aoe_scheduler/scheduleManager'); /* @var $scheduleManager Aoe_Scheduler_Model_ScheduleManager */
        $scheduleManager->logRun();

        $helper = Mage::helper('aoe_scheduler'); /* @var Aoe_Scheduler_Helper_Data $helper */
        $includeJobs = $helper->addGroupJobs((array)$observer->getIncludeJobs(), (array)$observer->getIncludeGroups());
        $excludeJobs = $helper->addGroupJobs((array)$observer->getExcludeJobs(), (array)$observer->getExcludeGroups());

        $schedules = $scheduleManager->getPendingSchedules($includeJobs, $excludeJobs); /* @var $schedules Mage_Cron_Model_Resource_Schedule_Collection */
        foreach ($schedules as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Schedule */
            $schedule->process();
        }

        $scheduleManager->generateSchedules();
        $scheduleManager->cleanup();


    }

    /**
     * Process cron queue for tasks marked as 'always'
     *
     * @param Varien_Event_Observer $observer
     */
    public function dispatchAlways(Varien_Event_Observer $observer)
    {
        if (!Mage::getStoreConfigFlag('system/cron/enable')) {
            return;
        }

        $processManager = Mage::getModel('aoe_scheduler/processManager'); /* @var $processManager Aoe_Scheduler_Model_ProcessManager */
        $processManager->watchdog();

        $scheduleManager = Mage::getModel('aoe_scheduler/scheduleManager'); /* @var $scheduleManager Aoe_Scheduler_Model_ScheduleManager */

        $helper = Mage::helper('aoe_scheduler'); /* @var Aoe_Scheduler_Helper_Data $helper */
        $includeJobs = $helper->addGroupJobs((array)$observer->getIncludeJobs(), (array)$observer->getIncludeGroups());
        $excludeJobs = $helper->addGroupJobs((array)$observer->getExcludeJobs(), (array)$observer->getExcludeGroups());

        /* @var $jobs Aoe_Scheduler_Model_Resource_Job_Collection */
        $jobs = Mage::getSingleton('aoe_scheduler/job')->getCollection();
        $jobs->setWhiteList($includeJobs);
        $jobs->setBlackList($excludeJobs);
        $jobs->setActiveOnly(true);
        foreach ($jobs as $job) {
            /* @var Aoe_Scheduler_Model_Job $job */
            if ($job->isAlwaysTask() && $job->getRunModel()) {
                $schedule = $scheduleManager->getScheduleForAlwaysJob($job->getJobCode());
                if ($schedule !== false) {
                    $schedule->process();
                }
            }
        }
    }
}
