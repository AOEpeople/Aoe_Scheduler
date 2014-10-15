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

        $scheduleManager = Mage::getModel('aoe_scheduler/scheduleManager'); /* @var $scheduleManager Aoe_Scheduler_Model_ScheduleManager */
        $scheduleManager->logRun();

        $includeGroups = array_filter(array_map('trim', (array)$observer->getIncludeGroups()));
        $excludeGroups = array_filter(array_map('trim', (array)$observer->getExcludeGroups()));

        $helper = Mage::helper('aoe_scheduler'); /* @var $helper Aoe_Scheduler_Helper_Data */

        $schedules = $scheduleManager->getPendingSchedules(); /* @var $schedules Mage_Cron_Model_Resource_Schedule_Collection */
        foreach ($schedules as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Schedule */
            if ($helper->matchesIncludeExclude($schedule->getJobCode(), $includeGroups, $excludeGroups)) {
                $schedule->process();
            }
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
    public function dispatchAlways(Varien_Event_Observer $observer)
    {
        if (!Mage::getStoreConfigFlag('system/cron/enable')) {
            return;
        }

        $processManager = Mage::getModel('aoe_scheduler/processManager'); /* @var $processManager Aoe_Scheduler_Model_ProcessManager */
        $processManager->processKillRequests();

        $scheduleManager = Mage::getModel('aoe_scheduler/scheduleManager'); /* @var $scheduleManager Aoe_Scheduler_Model_ScheduleManager */

        $helper = Mage::helper('aoe_scheduler'); /* @var $helper Aoe_Scheduler_Helper_Data */
        $includeGroups = array_filter(array_map('trim', (array)$observer->getIncludeGroups()));
        $excludeGroups = array_filter(array_map('trim', (array)$observer->getExcludeGroups()));

        $allJobs = Mage::getModel('aoe_scheduler/job_factory')->getAllJobs(); /* @var $allJobs Varien_Data_Collection */
        foreach ($allJobs as $job) { /* @var $job Aoe_Scheduler_Model_Job_Abstract */
            if ($job->isAlwaysTask() && $job->getRunModel()) {
                if ($helper->matchesIncludeExclude($job->getJobCode(), $includeGroups, $excludeGroups)) {
                    $schedule = $scheduleManager->getScheduleForAlwaysJob($job->getJobCode());
                    if ($schedule !== false) {
                        $schedule->process();
                    }
                }
            }
        }
    }

}