<?php

/**
 * Graceful Dead Helper
 *
 * @author Fabrizio Branca
 * @since 2015-07-02
 */
class Aoe_Scheduler_Helper_GracefulDead
{

    /**
     * Configure graceful dead
     */
    public static function configure()
    {
        static $configured = false;
        if (!$configured) {
            register_shutdown_function(array('Aoe_Scheduler_Helper_GracefulDead', 'beforeDyingShutdown'));
            if (extension_loaded('pcntl')) {
                declare(ticks = 1);
                pcntl_signal(SIGINT, array('Aoe_Scheduler_Helper_GracefulDead', 'beforeDyingSigint')); // CTRL + C
                pcntl_signal(SIGTERM, array('Aoe_Scheduler_Helper_GracefulDead', 'beforeDyingSigterm')); // kill <pid>
            }
            $configured = true;
        }
    }

    public static function beforeDying($message = null, $exit = false)
    {
        $schedule = Mage::registry('currently_running_schedule');  /* @var $schedule Aoe_Scheduler_Model_Schedule */
        if ($schedule !== null) {
            if ($message) {
                $schedule->addMessages($message);
            }
            $schedule
                ->setStatus(Aoe_Scheduler_Model_Schedule::STATUS_DIED)
                ->setFinishedAt(strftime('%Y-%m-%d %H:%M:%S', time()))
                ->save();
            Mage::unregister('currently_running_schedule');
        }
        if ($exit) {
            exit;
        }
    }

    /**
     * Callback
     */
    public static function beforeDyingShutdown()
    {
        self::beforeDying('TRIGGER: shutdown function', false);
    }

    /**
     * Callback
     */
    public static function beforeDyingSigint()
    {
        self::beforeDying('TRIGGER: Signal SIGINT', true);
    }

    /**
     * Callback
     */
    public static function beforeDyingSigterm()
    {
        self::beforeDying('TRIGGER: Signal SIGTERM', true);
    }
}
