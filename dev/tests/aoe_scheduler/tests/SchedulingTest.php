<?php

class SchedulingTest extends AbstractTest
{

    /**
     * @test
     */
    public function generateSchedule()
    {
        $scheduleManager = Mage::getModel('aoe_scheduler/scheduleManager'); /* @var Aoe_Scheduler_Model_ScheduleManager $scheduleManager */

        $scheduleManager->deleteAll();
        $collection = Mage::getModel('cron/schedule')->getCollection();
        $this->assertCount(0, $collection);

        $scheduleManager->generateSchedules();
        $collection = Mage::getModel('cron/schedule')->getCollection(); /* @var $collection Mage_Cron_Model_Resource_Schedule_Collection */
        $this->assertGreaterThan(0, $collection->count());

        $scheduleManager->deleteAll();
        $collection = Mage::getModel('cron/schedule')->getCollection();
        $this->assertCount(0, $collection);
    }

    /**
     * @param $runCronCallBack callable
     * @dataProvider runCronDefaultProvider
     * @test
     */
    public function scheduleJobAndRunCron($runCronCallBack)
    {
        // delete all schedules
        $scheduleManager = Mage::getModel('aoe_scheduler/scheduleManager'); /* @var Aoe_Scheduler_Model_ScheduleManager $scheduleManager */
        $scheduleManager->deleteAll();

        // fake schedule generation to avoid it to be generated on the next run:
        Mage::app()->saveCache(time(), Mage_Cron_Model_Observer::CACHE_KEY_LAST_SCHEDULE_GENERATE_AT, array('crontab'), null);

        $schedule = Mage::getModel('cron/schedule'); /* @var $schedule Aoe_Scheduler_Model_Schedule */
        $jobCode = 'aoescheduler_testtask';
        $schedule->setJobCode($jobCode);
        $schedule->schedule();
        $schedule->setScheduledReason('unittest');
        $schedule->save();
        $scheduleId = $schedule->getId();
        $this->assertGreaterThan(0, intval($scheduleId));

        // check for pending status
        $loadedSchedule = Mage::getModel('cron/schedule')->load($scheduleId); /* @var Aoe_Scheduler_Model_Schedule $loadedSchedule */
        $this->assertEquals($scheduleId, $loadedSchedule->getId());
        $this->assertEquals(Aoe_Scheduler_Model_Schedule::STATUS_PENDING, $loadedSchedule->getStatus());

        // run cron
        $runCronCallBack();

        // check for success status
        $loadedSchedule = Mage::getModel('cron/schedule')->load($scheduleId); /* @var Aoe_Scheduler_Model_Schedule $loadedSchedule */
        $this->assertEquals($scheduleId, $loadedSchedule->getId());
        $this->assertEquals(Aoe_Scheduler_Model_Schedule::STATUS_SUCCESS, $loadedSchedule->getStatus());
    }
}
