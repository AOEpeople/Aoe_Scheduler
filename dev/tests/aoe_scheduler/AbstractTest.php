<?php

abstract class AbstractTest extends PHPUnit_Framework_TestCase
{

    protected $jobs = array();
    protected $schedules = array();

    protected function setUp()
    {
        parent::setUp();
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

    /**
     * Provider for a callback that executed a cron run
     *
     * @return array
     */
    public function runCronAlwaysProvider()
    {
        return array(
            array(function () {
                // trigger dispatch
                $observer = Mage::getModel('aoe_scheduler/observer'); /* @var $observer Aoe_Scheduler_Model_Observer */
                $observer->dispatchAlways(new Varien_Event_Observer());
            }),
            array(function () {
                shell_exec('/usr/bin/php ' . Mage::getBaseDir() . '/cron.php');
                shell_exec('cd ' . Mage::getBaseDir() . '/shell && /usr/bin/php scheduler.php --action wait');
            }),
            array(function () {
                shell_exec('/bin/sh ' . Mage::getBaseDir() . '/cron.sh');
                shell_exec('cd ' . Mage::getBaseDir() . '/shell && /usr/bin/php scheduler.php --action wait');
            }),
            array(function () {
                shell_exec('/bin/sh ' . Mage::getBaseDir() . '/cron.sh cron.php -mdefault 1');
                shell_exec('cd ' . Mage::getBaseDir() . '/shell && /usr/bin/php scheduler.php --action wait');
            }),
            array(function () {
                shell_exec('cd ' . Mage::getBaseDir() . '/shell && /usr/bin/php scheduler.php --action cron --mode default');
                shell_exec('cd ' . Mage::getBaseDir() . '/shell && /usr/bin/php scheduler.php --action wait');
            }),
            array(function () {
                shell_exec('/bin/bash ' . Mage::getBaseDir() . '/scheduler_cron.sh');
                shell_exec('cd ' . Mage::getBaseDir() . '/shell && /usr/bin/php scheduler.php --action wait');
            }),
            array(function () {
                shell_exec('/bin/bash ' . Mage::getBaseDir() . '/scheduler_cron.sh --mode default');
                shell_exec('cd ' . Mage::getBaseDir() . '/shell && /usr/bin/php scheduler.php --action wait');
            })
        );
    }

    /**
     * Provider for a callback that executed a cron run
     *
     * @return array
     */
    public function runCronDefaultProvider()
    {
        return array(
            array(function () {
                // trigger dispatch
                $observer = Mage::getModel('aoe_scheduler/observer'); /* @var $observer Aoe_Scheduler_Model_Observer */
                $observer->dispatch(new Varien_Event_Observer());
            }),
            array(function () {
                shell_exec('/usr/bin/php ' . Mage::getBaseDir() . '/cron.php');
                shell_exec('cd ' . Mage::getBaseDir() . '/shell && /usr/bin/php scheduler.php --action wait');
            }),
            array(function () {
                shell_exec('/bin/sh ' . Mage::getBaseDir() . '/cron.sh');
                shell_exec('cd ' . Mage::getBaseDir() . '/shell && /usr/bin/php scheduler.php --action wait');
            }),
            array(function () {
                shell_exec('/bin/sh ' . Mage::getBaseDir() . '/cron.sh cron.php -mdefault 1');
                shell_exec('cd ' . Mage::getBaseDir() . '/shell && /usr/bin/php scheduler.php --action wait');
            }),
            array(function () {
                shell_exec('cd ' . Mage::getBaseDir() . '/shell && /usr/bin/php scheduler.php --action cron --mode default');
                shell_exec('cd ' . Mage::getBaseDir() . '/shell && /usr/bin/php scheduler.php --action wait');
            }),
            array(function () {
                shell_exec('/bin/bash ' . Mage::getBaseDir() . '/scheduler_cron.sh');
                shell_exec('cd ' . Mage::getBaseDir() . '/shell && /usr/bin/php scheduler.php --action wait');
            }),
            array(function () {
                shell_exec('/bin/bash ' . Mage::getBaseDir() . '/scheduler_cron.sh --mode default');
                shell_exec('cd ' . Mage::getBaseDir() . '/shell && /usr/bin/php scheduler.php --action wait');
            })
        );
    }
}