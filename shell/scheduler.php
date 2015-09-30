<?php

require_once 'abstract.php';

class Aoe_Scheduler_Shell_Scheduler extends Mage_Shell_Abstract
{
    /**
     * Run script
     *
     * @return void
     */
    public function run()
    {
        try {
            $helper = Mage::helper('aoe_scheduler/compatibility'); /* @var $helper Aoe_Scheduler_Helper_Compatibility */
            if ($helper->oldConfigXmlExists()) {
                echo 'Looks like you have an older version of Aoe_Scheduler installed that lived in the local code pool. Please delete everything under "' .$helper->getLocalCodeDir(). '"';
                exit(1);
            }
            $action = $this->getArg('action');
            if (empty($action)) {
                echo $this->usageHelp();
            } else {
                $actionMethodName = $action . 'Action';
                if (method_exists($this, $actionMethodName)) {
                    // emulate index.php entry point for correct URLs generation in scheduled cronjobs
                    Mage::register('custom_entry_point', true);
                    // Disable use of SID in generated URLs - This is standard for cron job bootstrapping
                    Mage::app()->setUseSessionInUrl(false);
                    // Disable permissions masking by default - This is Magento standard, but not recommended for security reasons
                    umask(0);
                    // Load the global event area - This is non-standard be should be standard
                    Mage::app()->addEventArea(Mage_Core_Model_App_Area::AREA_GLOBAL);
                    // Load the crontab event area - This is standard for cron job bootstrapping
                    Mage::app()->addEventArea('crontab');
                    // Run the command
                    $this->$actionMethodName();
                } else {
                    echo "Action $action not found!\n";
                    echo $this->usageHelp();
                    exit(1);
                }
            }
        } catch (Exception $e) {
            $fh = fopen('php://stderr', 'w');
            fputs($fh, $e->__toString());
            fclose($fh);
            exit(255);
        }
    }

    /**
     * Retrieve Usage Help Message
     *
     * @return string
     */
    public function usageHelp()
    {
        $help = 'Available actions: ' . "\n";
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (substr($method, -6) == 'Action') {
                $help .= '    --action ' . substr($method, 0, -6);
                $helpMethod = $method . 'Help';
                if (method_exists($this, $helpMethod)) {
                    $help .= ' ' . $this->$helpMethod();
                }
                $help .= "\n";
            }
        }
        return $help;
    }

    /**
     * List all availables codes / jobs
     *
     * @return void
     */
    public function listAllCodesAction()
    {
        /** @var Aoe_Scheduler_Model_Resource_Job_Collection $jobs */
        $jobs = Mage::getSingleton('aoe_scheduler/job')->getCollection();
        foreach ($jobs as $job) {
            /* @var $job Aoe_Scheduler_Model_Job */
            echo sprintf("%-50s %-20s %s\n", $job->getJobCode(), $job->getCronExpression(), $job->getIsActive() ? 'Enabled' : 'Disabled');
        }
    }

    /**
     * Returns the timestamp of the last run of a given job
     *
     * @return void
     */
    public function lastRunAction()
    {
        $code = $this->getArg('code');
        if (empty($code)) {
            echo "\nNo code found!\n\n";
            echo $this->usageHelp();
            exit(1);
        }

        $collection = Mage::getModel('cron/schedule')->getCollection(); /* @var $collection Mage_Cron_Model_Resource_Schedule_Collection */

        $collection->addFieldToFilter('job_code', $code)
            ->addFieldToFilter('status', Aoe_Scheduler_Model_Schedule::STATUS_SUCCESS)
            ->addOrder('finished_at', Varien_Data_Collection_Db::SORT_ORDER_DESC)
            ->getSelect()->limit(1);
        $schedule = $collection->getFirstItem(); /* @var $schedule Aoe_Scheduler_Model_Schedule */
        if (!$schedule || !$schedule->getId()) {
            echo "\nNo schedule found\n\n";
            exit(1);
        }

        $time = strtotime($schedule->getFinishedAt());

        if ($this->getArg('secondsFromNow')) {
            $time = time() - $time;
        }

        echo $time . PHP_EOL;
    }

    /**
     * Display extra help
     *
     * @return string
     */
    public function lastRunActionHelp()
    {
        return "--code <code> [--secondsFromNow]	Get the timestamp of the last successful run of a job for a given code";
    }

    /**
     * Schedule a job now
     *
     * @return void
     */
    public function scheduleNowAction()
    {
        $code = $this->getArg('code');
        if (empty($code)) {
            echo "\nNo code found!\n\n";
            echo $this->usageHelp();
            exit(1);
        }

        $allowedCodes = Mage::getSingleton('aoe_scheduler/job')->getResource()->getJobCodes();
        if (!in_array($code, $allowedCodes)) {
            echo "\nNo valid job found!\n\n";
            echo $this->usageHelp();
            exit(1);
        }

        $schedule = Mage::getModel('cron/schedule'); /* @var $schedule Aoe_Scheduler_Model_Schedule */
        $schedule->setScheduledReason(Aoe_Scheduler_Model_Schedule::REASON_SCHEDULENOW_CLI);
        $schedule->setJobCode($code);
        $schedule->schedule();
        $schedule->save();
    }

    /**
     * Display extra help
     *
     * @return string
     */
    public function scheduleNowActionHelp()
    {
        return "--code <code>	Schedule a job to be executed as soon as possible";
    }

    /**
     * Run a job now
     *
     * @return void
     */
    public function runNowAction()
    {
        $code = $this->getArg('code');
        if (empty($code)) {
            echo "\nNo code found!\n\n";
            echo $this->usageHelp();
            exit(1);
        }

        $allowedCodes = Mage::getSingleton('aoe_scheduler/job')->getResource()->getJobCodes();
        if (!in_array($code, $allowedCodes)) {
            echo "\nNo valid job found!\n\n";
            echo $this->usageHelp();
            exit(1);
        }

        $forceRun = ($this->getArg('force'));
        $tryLock = ($this->getArg('tryLock'));

        $schedule = Mage::getModel('cron/schedule'); /* @var $schedule Aoe_Scheduler_Model_Schedule */
        $schedule->setJobCode($code);
        $schedule->setScheduledReason(Aoe_Scheduler_Model_Schedule::REASON_RUNNOW_CLI);
        $schedule->runNow($tryLock, $forceRun);
        if ($schedule->getJobWasLocked()) {
            echo "\nJob was not executed because it was locked!\n\n";
            exit(1);
        }
        $schedule->save();

        echo "\nStatus: " . $schedule->getStatus() . "\n";
        echo "Messages:\n" . trim($schedule->getMessages(), "\n") . "\n";
    }

    /**
     * Display extra help
     *
     * @return string
     */
    public function runNowActionHelp()
    {
        return "--code <code> [--tryLock] [--force]	        Run a job directly";
    }

    /**
     * Active wait until no schedules are running
     */
    public function waitAction()
    {
        $timeout = $this->getArg('timeout') ? $this->getArg('timeout') : 60;
        $startTime = time();
        $sleepBetweenPolls = 2;
        $processManager = Mage::getModel('aoe_scheduler/processManager'); /* @var $processManager Aoe_Scheduler_Model_ProcessManager */
        do {
            sleep($sleepBetweenPolls);
            $aliveSchedules = 0;
            echo "Currently running schedules:\n";
            foreach ($processManager->getAllRunningSchedules() as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Schedule */
                $status = $schedule->isAlive();
                if (is_null($status)) {
                    $status = '?';
                } else {
                    $status = $status ? 'alive' : 'dead (updating status to "disappeared")';
                }
                if ($status) {
                    $aliveSchedules++;
                }
                echo sprintf(
                    "%-30s %-10s %-10s %-10s %-10s\n",
                    $schedule->getJobCode(),
                    $schedule->getHost() ? $schedule->getHost() : '(no host)',
                    $schedule->getPid() ? $schedule->getPid() : '(no pid)',
                    $schedule->getLastSeen() ? $schedule->getLastSeen() : '(never)',
                    $status
                );
            }
            if ($aliveSchedules == 0) {
                echo "No schedules found\n";
                return;
            }
        } while (time() - $startTime < $timeout);
        echo "Timeout reached\n";
        exit(1);
    }

    /**
     * Display extra help
     *
     * @return string
     */
    public function waitActionHelp()
    {
        return "[--timout <timeout=60>]	        Active wait until no schedules are running.";
    }

    /**
     * Flush schedules
     */
    public function flushSchedulesAction()
    {
        $scheduleManager = Mage::getModel('aoe_scheduler/scheduleManager'); /* @var $scheduleManager Aoe_Scheduler_Model_ScheduleManager */
        switch ($this->getArg('mode')) {
            case 'future':
                $scheduleManager->flushSchedules();
                break;
            case 'all':
                $scheduleManager->deleteAll();
                break;
            default:
                echo "\nInvalid mode!\n\n";
                echo $this->usageHelp();
                exit(1);
        }
    }

    /**
     * Display extra help
     *
     * @return string
     */
    public function flushSchedulesActionHelp()
    {
        return "--mode (future|all)	        Flush schedules.";
    }

    /**
     * Print all running schedules
     *
     * @return void
     */
    public function listAllRunningSchedulesAction()
    {
        $processManager = Mage::getModel('aoe_scheduler/processManager'); /* @var $processManager Aoe_Scheduler_Model_ProcessManager */
        foreach ($processManager->getAllRunningSchedules() as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Schedule */
            $status = $schedule->isAlive();
            if (is_null($status)) {
                $status = '?';
            } else {
                $status = $status ? 'alive' : 'dead (updating status to "disappeared")';
            }
            echo sprintf(
                "%-30s %-10s %-10s %-10s %-10s\n",
                $schedule->getJobCode(),
                $schedule->getHost() ? $schedule->getHost() : '(no host)',
                $schedule->getPid() ? $schedule->getPid() : '(no pid)',
                $schedule->getLastSeen() ? $schedule->getLastSeen() : '(never)',
                $status
            );
        }
    }

    /**
     * Kill all
     *
     * @return void
     */
    public function killAllAction()
    {
        $processManager = Mage::getModel('aoe_scheduler/processManager'); /* @var $processManager Aoe_Scheduler_Model_ProcessManager */
        foreach ($processManager->getAllRunningSchedules(gethostname()) as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Schedule */
            if ($schedule->isAlive() === true) {
                $schedule->kill();
                echo sprintf(
                    "%-30s %-10s %-10s: Killed\n",
                    $schedule->getJobCode(),
                    $schedule->getHost(),
                    $schedule->getPid()
                );
            }
        }
    }

    /**
     * Runs watchdog
     */
    public function watchdogAction()
    {
        $processManager = Mage::getModel('aoe_scheduler/processManager'); /* @var $processManager Aoe_Scheduler_Model_ProcessManager */
        $processManager->watchdog();
    }

    /**
     * Cron action
     *
     *
     */
    public function cronAction()
    {
        $mode = $this->getArg('mode');
        switch ($mode) {
            case 'always':
            case 'default':
                $includeGroups = array_filter(array_map('trim', explode(',', $this->getArg('includeGroups'))));
                $excludeGroups = array_filter(array_map('trim', explode(',', $this->getArg('excludeGroups'))));
                $includeJobs = array_filter(array_map('trim', explode(',', $this->getArg('includeJobs'))));
                $excludeJobs = array_filter(array_map('trim', explode(',', $this->getArg('excludeJobs'))));
                Mage::dispatchEvent($mode, array(
                    'include_groups' => $includeGroups,
                    'exclude_groups' => $excludeGroups,
                    'include_jobs' => $includeJobs,
                    'exclude_jobs' => $excludeJobs,
                ));
                break;
            default:
                echo "\nInvalid mode!\n\n";
                echo $this->usageHelp();
                exit(1);
        }
    }

    /**
     * Display extra help
     *
     * @return string
     */
    public function cronActionHelp()
    {
        return "--mode (always|default) [--includeJobs <comma separated list of jobs>] [--excludeJobs <comma separated list of jobs>] [--includeGroups <comma separated list of groups>] [--excludeGroups <comma separated list of groups>]";
    }

    protected function _applyPhpVariables()
    {
        // Disable this feature as cron jobs should run with CLI settings only
    }
}

$shell = new Aoe_Scheduler_Shell_Scheduler();
$shell->run();
