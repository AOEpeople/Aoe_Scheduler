<?php

/**
 * Class Aoe_Scheduler_Model_TestTask
 *
 * @author Fabrizio Branca
 * @since 2013-10-10
 */
class Aoe_Scheduler_Model_TestTask
{

    /**
     * Run a test task
     *
     * @param Aoe_Scheduler_Model_Schedule $schedule
     */
    public function run(Aoe_Scheduler_Model_Schedule $schedule)
    {
        $starttime = time();
        // $endtime = $starttime + rand(180, 360);
        $endtime = $starttime + 5;
        $schedule
            ->setEta(strftime('%Y-%m-%d %H:%M:%S', $endtime))
            ->save();
        while ($endtime > time()) {
            sleep(5);
            $schedule
                ->setProgressMessage('Work in progress. Time spent: ' . (time() - $starttime))
                ->setEta(strftime('%Y-%m-%d %H:%M:%S', $endtime))
                ->save();
        }

        $schedule
            ->setProgressMessage('')
            ->save();

        /*
        if (rand(0, 1) == 0) {
            throw new Exception('This is a dummy exception');
        }
        */
    }

}