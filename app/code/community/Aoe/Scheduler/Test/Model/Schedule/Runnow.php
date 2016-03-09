<?php

class Aoe_Scheduler_Test_Model_Schedule_Runnow extends EcomDev_PHPUnit_Test_Case
{

    public function setup()
    {
        // delete all schedules
        $scheduleManager = Mage::getModel('aoe_scheduler/scheduleManager'); /* @var Aoe_Scheduler_Model_ScheduleManager $scheduleManager */
        $scheduleManager->deleteAll();
    }

    /**
     * @test
     */
    public function runJob()
    {
        $schedule = Mage::getModel('cron/schedule');

        $jobCode = 'aoescheduler_testtask';

        $schedule->setJobCode($jobCode);
        $schedule->runNow(false);

        $scheduleId = $schedule->getId();
        $this->assertGreaterThan(0, intval($schedule->getId()));

        /* @var Aoe_Scheduler_Model_Schedule $loadedSchedule */
        $loadedSchedule = Mage::getModel('cron/schedule')->load($scheduleId);
        $this->assertEquals($scheduleId, $loadedSchedule->getId());

        $this->assertEquals(gethostname(), $loadedSchedule->getHost());
        $this->assertEquals(getmypid(), $loadedSchedule->getPid());

        $this->assertEquals(Aoe_Scheduler_Model_Schedule::STATUS_SUCCESS, $loadedSchedule->getStatus());

        $this->assertEventDispatched(
            array(
                'cron_after',
                'cron_after_success',
                'cron_' . $jobCode . '_after',
                'cron_' . $jobCode . '_after_success',
                'cron_before',
                'cron_' . $jobCode . '_before',
            )
        );
    }

    /**
     * @test
     */
    public function runJobWithError()
    {
        $schedule = Mage::getModel('cron/schedule');

        $jobCode = 'aoescheduler_testtask';

        $parameter = array('outcome' => 'error');

        $schedule->setJobCode($jobCode);
        $schedule->setParameters(json_encode($parameter));
        $schedule->runNow(false);

        $scheduleId = $schedule->getId();
        $this->assertGreaterThan(0, intval($schedule->getId()));

        /* @var Aoe_Scheduler_Model_Schedule $loadedSchedule */
        $loadedSchedule = Mage::getModel('cron/schedule')->load($scheduleId);
        $this->assertEquals($scheduleId, $loadedSchedule->getId());

        $this->assertEquals(gethostname(), $loadedSchedule->getHost());
        $this->assertEquals(getmypid(), $loadedSchedule->getPid());

        $this->assertEquals(Aoe_Scheduler_Model_Schedule::STATUS_ERROR, $loadedSchedule->getStatus());

        $this->assertEventDispatched(
            array(
                'cron_after',
                'cron_after_error',
                'cron_' . $jobCode . '_after',
                'cron_' . $jobCode . '_after_error',
                'cron_before',
                'cron_' . $jobCode . '_before',
            )
        );
    }

    /**
     * @test
     */
    public function runJobWithNothing()
    {
        $schedule = Mage::getModel('cron/schedule');

        $jobCode = 'aoescheduler_testtask';

        $parameter = array('outcome' => 'nothing');

        $schedule->setJobCode($jobCode);
        $schedule->setParameters(json_encode($parameter));
        $schedule->runNow(false);

        $scheduleId = $schedule->getId();
        $this->assertGreaterThan(0, intval($schedule->getId()));

        /* @var Aoe_Scheduler_Model_Schedule $loadedSchedule */
        $loadedSchedule = Mage::getModel('cron/schedule')->load($scheduleId);
        $this->assertEquals($scheduleId, $loadedSchedule->getId());

        $this->assertEquals(gethostname(), $loadedSchedule->getHost());
        $this->assertEquals(getmypid(), $loadedSchedule->getPid());

        $this->assertEquals(Aoe_Scheduler_Model_Schedule::STATUS_DIDNTDOANYTHING, $loadedSchedule->getStatus());

        $this->assertEventDispatched(
            array(
                'cron_after',
                'cron_after_nothing',
                'cron_' . $jobCode . '_after',
                'cron_' . $jobCode . '_after_nothing',
                'cron_before',
                'cron_' . $jobCode . '_before',
            )
        );
    }

    /**
     * @test
     */
    public function runJobWithException()
    {
        $schedule = Mage::getModel('cron/schedule');

        $jobCode = 'aoescheduler_testtask';

        $parameter = array('outcome' => 'exception');

        $schedule->setJobCode($jobCode);
        $schedule->setParameters(json_encode($parameter));
        $schedule->runNow(false);

        $scheduleId = $schedule->getId();
        $this->assertGreaterThan(0, intval($schedule->getId()));

        /* @var Aoe_Scheduler_Model_Schedule $loadedSchedule */
        $loadedSchedule = Mage::getModel('cron/schedule')->load($scheduleId);
        $this->assertEquals($scheduleId, $loadedSchedule->getId());

        $this->assertEquals(gethostname(), $loadedSchedule->getHost());
        $this->assertEquals(getmypid(), $loadedSchedule->getPid());

        $this->assertEquals(Aoe_Scheduler_Model_Schedule::STATUS_ERROR, $loadedSchedule->getStatus());

        $this->assertEventDispatched(
            array(
                'cron_after',
                'cron_exception',
                'cron_' . $jobCode . '_after',
                'cron_' . $jobCode . '_exception',
                'cron_before',
                'cron_' . $jobCode . '_before',
            )
        );
    }

    /**
     * When the runningAsConfiguredUser returns false (which is when kill is enabled) the job should not run
     * but return an instance of itself and set a status message
     *
     * @loadFixture killOnWrongUser
     */
    public function testShouldKillWithWrongUserAndKillSwitchSet()
    {
        $result = $this->_performConfiguredUserTest();
        $this->assertSame(Aoe_Scheduler_Model_Schedule::STATUS_SKIP_WRONGUSER, $result->getStatus());
    }

    /**
     * When "warn" is the chosen preference for what to do when the wrong user runs the cron,
     * allow the job to complete
     *
     * @loadFixture warnOnWrongUser
     */
    public function testShouldNotKillWhenKillSwitchIsOffButUserIsWrong()
    {
        $result = $this->_performConfiguredUserTest();
        $this->assertSame(Aoe_Scheduler_Model_Schedule::STATUS_SUCCESS, $result->getStatus());
    }

    /**
     * Centralized logic for running unit test against multiple fixtures
     * @return Aoe_Scheduler_Model_Schedule
     */
    protected function _performConfiguredUserTest()
    {
        $helperMock = $this->getHelperMock('aoe_scheduler', array('runningAsConfiguredUser'));
        $helperMock->expects($this->once())->method('runningAsConfiguredUser')->will($this->returnValue(false));
        $this->replaceByMock('helper', 'aoe_scheduler', $helperMock);

        $schedule = Mage::getModel('cron/schedule');
        $result = $schedule->setJobCode('aoescheduler_testtask')->runNow(false);

        $this->assertInstanceOf('Aoe_Scheduler_Model_Schedule', $result);
        return $result;
    }
}
