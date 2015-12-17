<?php

class CronGroupsWhiteListTest extends AbstractTest
{

    protected $groups = array();

    protected function setUp()
    {
        parent::setUp();

        $this->groups['groupA'] = uniqid('groupA_');
        $this->groups['groupB'] = uniqid('groupB_');

        $jobWithGroupA = Mage::getModel('aoe_scheduler/job'); /* @var $jobWithGroupA Aoe_Scheduler_Model_Job */
        $jobWithGroupA->setJobCode(uniqid('t_job_'));
        $jobWithGroupA->setRunModel('aoe_scheduler/task_test::run');
        $jobWithGroupA->setGroups($this->groups['groupA']);
        $jobWithGroupA->setIsActive(true);
        $jobWithGroupA->save();
        $this->jobs['jobWithGroupA'] = $jobWithGroupA;

        $jobWithGroupB = Mage::getModel('aoe_scheduler/job'); /* @var $jobWithGroupB Aoe_Scheduler_Model_Job */
        $jobWithGroupB->setJobCode(uniqid('t_job_'));
        $jobWithGroupB->setRunModel('aoe_scheduler/task_test::run');
        $jobWithGroupB->setGroups($this->groups['groupB']);
        $jobWithGroupB->setIsActive(true);
        $jobWithGroupB->save();
        $this->jobs['jobWithGroupB'] = $jobWithGroupB;

        $jobWithGroupAandB = Mage::getModel('aoe_scheduler/job'); /* @var $jobWithGroupAandB Aoe_Scheduler_Model_Job */
        $jobWithGroupAandB->setJobCode(uniqid('t_job_'));
        $jobWithGroupAandB->setRunModel('aoe_scheduler/task_test::run');
        $jobWithGroupAandB->setGroups("{$this->groups['groupA']},{$this->groups['groupB']}");
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

        // fake schedule generation to avoid it to be generated on the next run:
        Mage::app()->saveCache(time(), Mage_Cron_Model_Observer::CACHE_KEY_LAST_SCHEDULE_GENERATE_AT, array('crontab'), null);
    }

    /**
     * @test
     */
    public function scheduleJobAndRunCron()
    {

        foreach ($this->schedules as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Schedule */
            $this->assertEquals(Aoe_Scheduler_Model_Schedule::STATUS_PENDING, $schedule->refresh()->getStatus());
            // echo "Job code: {$schedule->getJobCode()}, Id: {$schedule->getId()}, Groups: " . $schedule->getJob()->getGroups() . "\n";
        }

        // check if the new jobs show up in the "groups to jobs map"
        $helper = Mage::helper('aoe_scheduler'); /* @var $helper Aoe_Scheduler_Helper_Data */
        $map = $helper->getGroupsToJobsMap();

        foreach ($this->groups as $group) {
            $this->assertArrayHasKey($group, $map);
        }
        $this->assertTrue(in_array($this->schedules['jobWithGroupA']->getJobCode(), $map[$this->groups['groupA']]));
        $this->assertTrue(in_array($this->schedules['jobWithGroupB']->getJobCode(), $map[$this->groups['groupB']]));
        $this->assertTrue(in_array($this->schedules['jobWithGroupAandB']->getJobCode(), $map[$this->groups['groupA']]));
        $this->assertTrue(in_array($this->schedules['jobWithGroupAandB']->getJobCode(), $map[$this->groups['groupB']]));

        $includeJobs = $helper->addGroupJobs(array(), array($this->groups['groupA']));
        $this->assertTrue(in_array($this->schedules['jobWithGroupA']->getJobCode(), $includeJobs));
        $this->assertTrue(in_array($this->schedules['jobWithGroupAandB']->getJobCode(), $includeJobs));

        $includeJobs = $helper->addGroupJobs(array(), array($this->groups['groupB']));
        $this->assertTrue(in_array($this->schedules['jobWithGroupB']->getJobCode(), $includeJobs));
        $this->assertTrue(in_array($this->schedules['jobWithGroupAandB']->getJobCode(), $includeJobs));

        $sameRequest = false;

        if ($sameRequest) {
            // dispatch event
            $event = new Varien_Event_Observer(array('include_groups' => array($this->groups['groupA'])));
            $observer = new Aoe_Scheduler_Model_Observer();
            $observer->dispatch($event);
        } else {
            $this->exec('cd ' . Mage::getBaseDir() . '/shell && /usr/bin/php scheduler.php --action cron --mode default --includeGroups ' . $this->groups['groupA']);
        }

        //$this->exec('cd ' . Mage::getBaseDir() . '/shell && /usr/bin/php scheduler.php --action wait');

        $this->assertEquals(Aoe_Scheduler_Model_Schedule::STATUS_SUCCESS, $this->schedules['jobWithGroupA']->refresh()->getStatus());
        $this->assertEquals(Aoe_Scheduler_Model_Schedule::STATUS_PENDING, $this->schedules['jobWithGroupB']->refresh()->getStatus());
        $this->assertEquals(Aoe_Scheduler_Model_Schedule::STATUS_SUCCESS, $this->schedules['jobWithGroupAandB']->refresh()->getStatus());
    }

}