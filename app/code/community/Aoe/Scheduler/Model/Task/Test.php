<?php

/**
 * Test Task
 *
 * @author Fabrizio Branca
 * @since 2013-10-10
 */
class Aoe_Scheduler_Model_Task_Test
{

    /**
     * General purpose test task.
     * Behavior can be controlled via parameters
     *
     * @param Aoe_Scheduler_Model_Schedule $schedule
     * @return string
     * @throws Exception
     */
    public function run(Aoe_Scheduler_Model_Schedule $schedule)
    {
        $parameters = $schedule->getParameters();
        if ($parameters) {
            $parameters = json_decode($parameters, true);
        }

        // fake duration
        $duration = 0;
        if ($parameters && isset($parameters['duration'])) {
            $duration = $parameters['duration'];
        }
        sleep($duration);

        /* // testing the error log feature...
        array_keys('ssdsd');
        error_log( "Hello, errors!" );
        $t = I_AM_NOT_DEFINED;
        */

        if ($parameters && $parameters['outcome'] == 'error') {
            return 'ERROR: This schedule has failed.';
        }

        if ($parameters && $parameters['outcome'] == 'nothing') {
            return 'NOTHING: Did not do anything';
        }

        if ($parameters && $parameters['outcome'] == 'repeat') {
            return 'REPEAT';
        }

        if ($parameters && $parameters['outcome'] == 'exception') {
            throw new Exception('This is a dummy exception');
        }


        // Simulating ETA;
//        $starttime = time();
//        // $endtime = $starttime + rand(180, 360);
//        $endtime = $starttime + $duration;
//        $schedule
//            ->setEta(strftime('%Y-%m-%d %H:%M:%S', $endtime))
//            ->save();
//        while ($endtime > time()) {
//            sleep(5);
//            $schedule
//                ->setProgressMessage('Work in progress. Time spent: ' . (time() - $starttime))
//                ->setEta(strftime('%Y-%m-%d %H:%M:%S', $endtime))
//                ->save();
//        }
//
//        $schedule
//            ->setProgressMessage('')
//            ->save();
    }
}
