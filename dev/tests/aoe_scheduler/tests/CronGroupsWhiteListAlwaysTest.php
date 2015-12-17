<?php

class CronGroupsWhiteListAlwaysTest extends AbstractTest
{

    protected $groups = array();

    protected function setUp()
    {
        parent::setUp();

        $this->groups['groupA'] = uniqid('groupA_');
        $this->groups['groupB'] = uniqid('groupB_');

        $jobWithGroupA = Mage::getModel('aoe_scheduler/job'); /* @var $jobWithGroupA Aoe_Scheduler_Model_Job */
        $jobWithGroupA->setScheduleCronExpr('always');
        $jobWithGroupA->setJobCode(uniqid('t_job_'));
        $jobWithGroupA->setRunModel('aoe_scheduler/task_test::run');
        $jobWithGroupA->setGroups($this->groups['groupA']);
        $jobWithGroupA->setIsActive(true);
        $jobWithGroupA->save();
        $this->jobs['jobWithGroupA'] = $jobWithGroupA;

        $jobWithGroupB = Mage::getModel('aoe_scheduler/job'); /* @var $jobWithGroupB Aoe_Scheduler_Model_Job */
        $jobWithGroupB->setScheduleCronExpr('always');
        $jobWithGroupB->setJobCode(uniqid('t_job_'));
        $jobWithGroupB->setRunModel('aoe_scheduler/task_test::run');
        $jobWithGroupB->setGroups($this->groups['groupB']);
        $jobWithGroupB->setIsActive(true);
        $jobWithGroupB->save();
        $this->jobs['jobWithGroupB'] = $jobWithGroupB;

        $jobWithGroupAandB = Mage::getModel('aoe_scheduler/job'); /* @var $jobWithGroupAandB Aoe_Scheduler_Model_Job */
        $jobWithGroupAandB->setScheduleCronExpr('always');
        $jobWithGroupAandB->setJobCode(uniqid('t_job_'));
        $jobWithGroupAandB->setRunModel('aoe_scheduler/task_test::run');
        $jobWithGroupAandB->setGroups("{$this->groups['groupA']},{$this->groups['groupB']}");
        $jobWithGroupAandB->setIsActive(true);
        $jobWithGroupAandB->save();
        $this->jobs['jobWithGroupAandB'] = $jobWithGroupAandB;

        // fake schedule generation to avoid it to be generated on the next run:
        Mage::app()->saveCache(time(), Mage_Cron_Model_Observer::CACHE_KEY_LAST_SCHEDULE_GENERATE_AT, array('crontab'), null);
    }

    /**
     * @test
     */
    public function scheduleJobAndRunCron()
    {
        $sameRequest = false;

        if ($sameRequest) {
            // dispatch event
            $event = new Varien_Event_Observer(array('include_groups' => array($this->groups['groupA'])));
            $observer = new Aoe_Scheduler_Model_Observer();
            $observer->dispatch($event);
        } else {
            $this->exec('cd ' . Mage::getBaseDir() . '/shell && /usr/bin/php scheduler.php --action cron --mode always --includeGroups ' . $this->groups['groupA']);
        }

        $schedulesJobWithGroupA = Mage::getModel('cron/schedule')->getCollection()->addFieldToFilter('job_code', $this->jobs['jobWithGroupA']->getJobCode());
        $this->assertCount(1, $schedulesJobWithGroupA);
        foreach ($schedulesJobWithGroupA as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Schedule */
            $this->assertEquals(Aoe_Scheduler_Model_Schedule::STATUS_SUCCESS, $schedule->getStatus());
        }

        $schedulesJobWithGroupB = Mage::getModel('cron/schedule')->getCollection()->addFieldToFilter('job_code', $this->jobs['jobWithGroupB']->getJobCode());
        $this->assertCount(0, $schedulesJobWithGroupB);

        $schedulesJobWithGroupAandB = Mage::getModel('cron/schedule')->getCollection()->addFieldToFilter('job_code', $this->jobs['jobWithGroupAandB']->getJobCode());
        $this->assertCount(1, $schedulesJobWithGroupAandB);
        foreach ($schedulesJobWithGroupAandB as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Schedule */
            $this->assertEquals(Aoe_Scheduler_Model_Schedule::STATUS_SUCCESS, $schedule->getStatus());
        }
    }

}