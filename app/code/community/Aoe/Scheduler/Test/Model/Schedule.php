<?php

class Aoe_Scheduler_Test_Model_Schedule extends EcomDev_PHPUnit_Test_Case
{
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
     * @depends checkClass
     */
    public function runTask(Aoe_Scheduler_Model_Schedule $schedule)
    {
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

        $this->assertEquals(Mage_Cron_Model_Schedule::STATUS_SUCCESS, $loadedSchedule->getStatus());

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
}

