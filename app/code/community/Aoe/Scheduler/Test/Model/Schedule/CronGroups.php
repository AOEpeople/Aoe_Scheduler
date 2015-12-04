<?php

class Aoe_Scheduler_Test_Model_Schedule_CronGroups extends EcomDev_PHPUnit_Test_Case
{

    protected $jobs = array();
    protected $schedules = array();

    public function setup() {
        parent::setup();

        // delete all schedules
        $scheduleManager = Mage::getModel('aoe_scheduler/scheduleManager'); /* @var Aoe_Scheduler_Model_ScheduleManager $scheduleManager */
        $scheduleManager->deleteAll();

        $jobWithGroupA = Mage::getModel('aoe_scheduler/job'); /* @var $jobWithGroupA Aoe_Scheduler_Model_Job */
        $jobWithGroupA->setJobCode(uniqid('t_job_'));
        $jobWithGroupA->setRunModel('aoe_scheduler/task_test::run');
        $jobWithGroupA->setGroups('groupA');
        $jobWithGroupA->setIsActive(true);
        $jobWithGroupA->save();
        $this->jobs['jobWithGroupA'] = $jobWithGroupA;

        $jobWithGroupB = Mage::getModel('aoe_scheduler/job'); /* @var $jobWithGroupB Aoe_Scheduler_Model_Job */
        $jobWithGroupB->setJobCode(uniqid('t_job_'));
        $jobWithGroupB->setRunModel('aoe_scheduler/task_test::run');
        $jobWithGroupB->setGroups('groupB');
        $jobWithGroupB->setIsActive(true);
        $jobWithGroupB->save();
        $this->jobs['jobWithGroupB'] = $jobWithGroupB;

        $jobWithGroupAandB = Mage::getModel('aoe_scheduler/job'); /* @var $jobWithGroupAandB Aoe_Scheduler_Model_Job */
        $jobWithGroupAandB->setJobCode(uniqid('t_job_'));
        $jobWithGroupAandB->setRunModel('aoe_scheduler/task_test::run');
        $jobWithGroupAandB->setGroups('groupA,groupB');
        $jobWithGroupAandB->setIsActive(true);
        $jobWithGroupAandB->save();
        $this->jobs['jobWithGroupAandB'] = $jobWithGroupAandB;

        foreach ($this->jobs as $name => $job) { /* @var $job Aoe_Scheduler_Model_Job */
            $schedule = Mage::getModel('cron/schedule'); /* @var $schedule Aoe_Scheduler_Model_Schedule */
            $schedule->setJobCode($job->getJobCode());
            $schedule->schedule();
            $schedule->setScheduledReason('unittest');
            $schedule->save();
            $this->schedules[$name] = $schedule;
        }

        Mage::app()->getCache()->clean();

        // fake schedule generation to avoid it to be generated on the next run:
        Mage::app()->saveCache(time(), Mage_Cron_Model_Observer::CACHE_KEY_LAST_SCHEDULE_GENERATE_AT, array('crontab'), null);
    }

    public function __tearDown()
    {
        foreach ($this->jobs as $job) { /* @var $job Aoe_Scheduler_Model_Job */
            $job->delete();
        }
        foreach ($this->schedules as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Job */
            $schedule->delete();
        }
        parent::tearDown();
    }

    /**
     * @test
     */
    public function scheduleJobAndRunCron()
    {
        foreach ($this->schedules as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Schedule */
            $this->assertEquals(Aoe_Scheduler_Model_Schedule::STATUS_PENDING, $schedule->refresh()->getStatus());
        }

        shell_exec('cd ' . Mage::getBaseDir() . '/shell && /usr/bin/php scheduler.php --action cron --mode default --includeGroups groupA');
        shell_exec('cd ' . Mage::getBaseDir() . '/shell && /usr/bin/php scheduler.php --action wait');

        $this->assertEquals(Aoe_Scheduler_Model_Schedule::STATUS_SUCCESS, $this->schedules['jobWithGroupA']->getStatus());
        $this->assertEquals(Aoe_Scheduler_Model_Schedule::STATUS_PENDING, $this->schedules['jobWithGroupB']->getStatus());
        $this->assertEquals(Aoe_Scheduler_Model_Schedule::STATUS_SUCCESS, $this->schedules['jobWithGroupAandB']->getStatus());
    }

}
