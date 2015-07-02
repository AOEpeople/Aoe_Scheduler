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
            register_shutdown_function(array('Aoe_Scheduler_Helper_GracefulDead', 'beforeDying_shutdown'));
            if (extension_loaded('pcntl')) {
                declare(ticks = 1);
                pcntl_signal(SIGINT, array('Aoe_Scheduler_Helper_GracefulDead', 'beforeDying_sigint')); // CTRL + C
                pcntl_signal(SIGTERM, array('Aoe_Scheduler_Helper_GracefulDead', 'beforeDying_sigterm')); // kill <pid>
            }
            $configured = true;
        }
    }

    public static function beforeDying($message=null, $exit=false)
    {
        if (isset($GLOBALS['currently_running_schedule'])) {
            $schedule = $GLOBALS['currently_running_schedule'];  /* @var $schedule Aoe_Scheduler_Model_Schedule */
            if ($message) {
                $schedule->addMessages($message);
            }
            $schedule
                ->setStatus(Aoe_Scheduler_Model_Schedule::STATUS_DIED)
                ->setFinishedAt(strftime('%Y-%m-%d %H:%M:%S', time()))
                ->save();
            unset($GLOBALS['currently_running_schedule']);
        }
        if ($exit) {
            exit;
        }
    }

    /**
     * Callback
     */
    public static function beforeDying_shutdown()
    {
        self::beforeDying('TRIGGER: shutdown function', false);
    }

    /**
     * Callback
     */
    public static function beforeDying_sigint()
    {
        self::beforeDying('TRIGGER: Signal SIGINT', true);
    }

    /**
     * Callback
     */
    public static function beforeDying_sigterm()
    {
        self::beforeDying('TRIGGER: Signal SIGTERM', true);
    }
}
