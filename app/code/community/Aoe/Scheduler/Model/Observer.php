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
        $includeJobs = $helper->addGroupJobs((array) $observer->getIncludeJobs(), (array) $observer->getIncludeGroups());
        $excludeJobs = $helper->addGroupJobs((array) $observer->getExcludeJobs(), (array) $observer->getExcludeGroups());

        // Coalesce all jobs that should have run before now, by job code, by marking the oldest entries as missed.
        $scheduleManager->cleanMissedSchedules();

        // Iterate over all pending jobs
        foreach ($scheduleManager->getPendingSchedules($includeJobs, $excludeJobs) as $schedule) {
            /* @var Aoe_Scheduler_Model_Schedule $schedule */
            $schedule->process();
        }

        // Generate new schedules
        $scheduleManager->generateSchedules();

        // Clean up schedule history
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
        $includeJobs = $helper->addGroupJobs((array) $observer->getIncludeJobs(), (array) $observer->getIncludeGroups());
        $excludeJobs = $helper->addGroupJobs((array) $observer->getExcludeJobs(), (array) $observer->getExcludeGroups());

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
