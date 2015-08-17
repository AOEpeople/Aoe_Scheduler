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
     * @return Aoe_Scheduler_Model_Schedule
     */
    public function checkClass()
    {
        /* @var Aoe_Scheduler_Model_Schedule $schedule */
        $schedule = Mage::getModel('cron/schedule');
        $this->assertInstanceOf('Aoe_Scheduler_Model_Schedule', $schedule);
        return $schedule;
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
        $schedule->setParameters(serialize($parameter));
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
        $schedule->setParameters(serialize($parameter));
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
        $schedule->setParameters(serialize($parameter));
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
}
