<?php

abstract class AbstractTest extends PHPUnit_Framework_TestCase
{

    protected $jobs = array();
    protected $schedules = array();

    public function setUp()
    {
        require_once(MAGENTO_ROOT . '/app/Mage.php' );
        Mage::app();

        // delete all schedules
        $scheduleManager = Mage::getModel('aoe_scheduler/scheduleManager'); /* @var Aoe_Scheduler_Model_ScheduleManager $scheduleManager */
        $scheduleManager->deleteAll();
    }

    public function tearDown()
    {
        foreach ($this->jobs as $job) { /* @var $job Aoe_Scheduler_Model_Job */
            $job->delete();
        }
        foreach ($this->schedules as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Job */
            $schedule->delete();
        }
        parent::tearDown();
    }

    protected function exec($command)
    {
        $startTime = microtime(true);
        $output = null;
        $returnValue = null;
        exec($command, $output, $returnValue);
        // var_dump($output, $returnValue);
        $duration = microtime(true) - $startTime;
    }
}