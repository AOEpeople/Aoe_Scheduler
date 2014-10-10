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
            $action = $this->getArg('action');
            if (empty($action)) {
                echo $this->usageHelp();
            } else {
                $actionMethodName = $action . 'Action';
                if (method_exists($this, $actionMethodName)) {
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
        $collection = Mage::getModel('aoe_scheduler/collection_crons');
        foreach ($collection as $configuration) {
            /* @var $configuration Aoe_Scheduler_Model_Configuration */
            echo sprintf("%-50s %-20s %s\n", $configuration->getId(), $configuration->getCronExpr(), $configuration->getStatus());
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

        $collection = Mage::getModel('cron/schedule')->getCollection();
        /* @var $collection Mage_Cron_Model_Resource_Schedule_Collection */
        $collection->addFieldToFilter('job_code', $code)
            ->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_SUCCESS)
            ->addOrder('finished_at', Varien_Data_Collection_Db::SORT_ORDER_DESC)
            ->getSelect()->limit(1);
        $schedule = $collection->getFirstItem();
        /* @var $schedule Aoe_Scheduler_Model_Schedule */
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
        $schedule = Mage::getModel('cron/schedule');
        /* @var $schedule Aoe_Scheduler_Model_Schedule */
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
        $schedule = Mage::getModel('cron/schedule');
        /* @var $schedule Aoe_Scheduler_Model_Schedule */
        $schedule->setJobCode($code);
        $schedule->runNow(false);
        if ($schedule->getJobWasLocked()) {
            echo "\nJob was not executed because it was locked!\n\n";
            exit(1);
        }
        $schedule->save();

        echo "Status: " . $schedule->getStatus() . "\nMessages:\n" . trim($schedule->getMessages(), "\n") . "\n";
    }

    /**
     * Display extra help
     *
     * @return string
     */
    public function runNowActionHelp()
    {
        return "--code <code>	        Run a job directly";
    }

    /**
     * Print all running schedules
     *
     * @return void
     */
    public function listAllRunningSchedulesAction()
    {
        $processManager = Mage::getModel('aoe_scheduler/processManager');
        /* @var $processManager Aoe_Scheduler_Model_ProcessManager */
        foreach ($processManager->getAllRunningSchedules() as $schedule) {
            /* @var $schedule Aoe_Scheduler_Model_Schedule */
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
        $processManager = Mage::getModel('aoe_scheduler/processManager');
        /* @var $processManager Aoe_Scheduler_Model_ProcessManager */
        foreach ($processManager->getAllRunningSchedules(gethostname()) as $schedule) {
            /* @var $schedule Aoe_Scheduler_Model_Schedule */
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

    public function cronAction()
    {
        Mage::app('admin')->setUseSessionInUrl(false);
        umask(0);

        $mode = $this->getArg('mode');
        switch ($mode) {
            case 'always':
            case 'default':
                $includeGroups = array_filter(array_map('trim', explode(',', $this->getArg('include'))));
                $excludeGroups = array_filter(array_map('trim', explode(',', $this->getArg('exclude'))));
                Mage::getConfig()->init()->loadEventObservers('crontab');
                Mage::app()->addEventArea('crontab');
                Mage::dispatchEvent($mode, array('include' => $includeGroups, 'exclude' => $excludeGroups));
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
        return "--mode (always|default) [--exclude <comma seperated list of groups>] [--include <comma seperated list of groups>]";
    }
}

$shell = new Aoe_Scheduler_Shell_Scheduler();
$shell->run();
