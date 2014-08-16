<?php

/**
 * Schedule
 *
 * @method string getExecutedAt()
 * @method string getFinishedAt()
 * @method string getStatus()
 * @method string getMessages()
 * @method string getCreatedAt()
 * @method string getScheduledAt()
 * @method string setJobCode($jobCode)
 * @method string getJobCode()
 * @method string setMessages()
 * @method string setExecutedAt()
 * @method string setCreatedAt()
 * @method string setScheduledAt()
 * @method string setStatus()
 * @method string setFinishedAt()
 * @method string getParameters()
 * @method string setParameters()
 * @method string setEta()
 * @method string getEta()
 * @method string setHost()
 * @method string getHost()
 * @method string setPid()
 * @method string getPid()
 * @method string setProgressMessage()
 * @method string getProgressMessage()
 * @method string getLastSeen()
 * @method string setLastSeen()
 * @method string getScheduledBy()
 * @method string setScheduledBy($scheduledBy)
 * @method string getScheduledReason()
 * @method string setScheduledReason($scheduledReason)
 */
class Aoe_Scheduler_Model_Schedule extends Mage_Cron_Model_Schedule
{

    CONST STATUS_KILLED = 'killed';
    CONST STATUS_DISAPPEARED = 'gone'; // the status field is limited to 7 characters
    CONST STATUS_DIDNTDOANYTHING = 'nothing';

    CONST REASON_RUNNOW_WEB = 'run_now_web';
    CONST REASON_SCHEDULENOW_WEB = 'schedule_now_web';
    CONST REASON_RUNNOW_CLI = 'run_now_cli';
    CONST REASON_SCHEDULENOW_CLI = 'schedule_now_cli';
    CONST REASON_GENERATESCHEDULES = 'generate_schedules';
    CONST REASON_DEPENDENCY_ALL = 'dependency_all';
    CONST REASON_DEPENDENCY_SUCCESS = 'dependency_success';
    CONST REASON_DEPENDENCY_FAILURE = 'dependency_failure';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'aoe_scheduler_schedule';

    /**
     * @var Aoe_Scheduler_Model_Job_Abstract
     */
    protected $job;

    /**
     * @var bool
     */
    protected $jobWasLocked = false;



    /**
     * Initialize from job
     *
     * @param Aoe_Scheduler_Model_Job_Abstract $job
     * @return $this
     */
    public function initializeFromJob(Aoe_Scheduler_Model_Job_Abstract $job)
    {
        $this->setJobCode($job->getJobCode());
        $this->setCronExpr($job->getCronExpression());
        $this->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING);
        return $this;
    }


    /**
     * Run this task now
     *
     * @param bool $tryLockJob
     * @return Aoe_Scheduler_Model_Schedule
     */
    public function runNow($tryLockJob = true)
    {

        // lock job (see below) prevents the exact same schedule from being executed from more than one process (or server)
        // the following check will prevent multiple schedules of the same type to be run in parallel
        $processManager = Mage::getModel('aoe_scheduler/processManager'); /* @var $processManager Aoe_Scheduler_Model_ProcessManager */
        if ($processManager->isJobCodeRunning($this->getJobCode(), $this->getId())) {
            $this->log(sprintf('Job "%s" (id: %s) will not be executed because there is already another process with the same job code running. Skipping.', $this->getJobCode(), $this->getId()));
            return $this;
        }

        // lock job requires the record to be saved and having status Mage_Cron_Model_Schedule::STATUS_PENDING
        // workaround could be to do this: $this->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING)->save();
        $this->jobWasLocked = false;
        if ($tryLockJob && !$this->tryLockJob()) {
            // another cron started this job intermittently, so skip it
            $this->jobWasLocked = true;
            $this->log(sprintf('Job "%s" (id: %s) is locked. Skipping.', $this->getJobCode(), $this->getId()));
            return $this;
        }

        // if this schedule doesn't exist yet, create it
        if (!$this->getCreatedAt()) {
            $this->schedule();
        }

        $callback = $this->getJob()->getCallback();

        $startTime = time();
        $this
            ->setExecutedAt(strftime('%Y-%m-%d %H:%M:%S', $startTime))
            ->setLastSeen(strftime('%Y-%m-%d %H:%M:%S', $startTime))
            ->setStatus(Mage_Cron_Model_Schedule::STATUS_RUNNING)
            ->setHost(gethostname())
            ->setPid(getmypid())
            ->save();

        Mage::dispatchEvent('cron_' . $this->getJobCode() . '_before', array('schedule' => $this));
        Mage::dispatchEvent('cron_before', array('schedule' => $this));

        $this->log('Start: ' . $this->getJobCode());

        Mage::unregister('current_cron_task');
        Mage::register('current_cron_task', $this);

        // this is where the actual task will be executed ...
        $messages = call_user_func_array($callback, array($this));

        $this->log('Stop: ' . $this->getJobCode());

        if (!empty($messages)) {
            if (is_object($messages)) {
                $messages = get_class($messages);
            } elseif (!is_scalar($messages)) {
                $messages = var_export($messages, 1);
            }
            $this->setMessages($messages);
        }

        // schedules can report an error state by returning a string that starts with "ERROR:"
        if (is_string($messages) && strtoupper(substr($messages, 0, 6)) == 'ERROR:') {
            $this->setStatus(Mage_Cron_Model_Schedule::STATUS_ERROR);
            Mage::helper('aoe_scheduler')->sendErrorMail($this, $messages);
            Mage::dispatchEvent('cron_' . $this->getJobCode() . '_after_error', array('schedule' => $this));
            Mage::dispatchEvent('cron_after_error', array('schedule' => $this));
        } elseif (is_string($messages) && strtoupper(substr($messages, 0, 7)) == 'NOTHING') {
            $this->setStatus(Aoe_Scheduler_Model_Schedule::STATUS_DIDNTDOANYTHING);
            Mage::dispatchEvent('cron_' . $this->getJobCode() . '_after_nothing', array('schedule' => $this));
            Mage::dispatchEvent('cron_after_nothing', array('schedule' => $this));
        } else {
            $this->setStatus(Mage_Cron_Model_Schedule::STATUS_SUCCESS);
            Mage::dispatchEvent('cron_' . $this->getJobCode() . '_after_success', array('schedule' => $this));
            Mage::dispatchEvent('cron_after_success', array('schedule' => $this));
        }

        $this->setFinishedAt(strftime('%Y-%m-%d %H:%M:%S', time()));
        Mage::dispatchEvent('cron_' . $this->getJobCode() . '_after', array('schedule' => $this));
        Mage::dispatchEvent('cron_after', array('schedule' => $this));

        $this->save();

        return $this;
    }


    /**
     * Flag that shows that a previous execution was prevented because the job was locked
     *
     * @return bool
     */
    public function getJobWasLocked()
    {
        return $this->jobWasLocked;
    }


    /**
     * Schedule this task to be executed as soon as possible
     *
     * @deprecated use Aoe_Scheduler_Model_Schedule::schedule() instead
     * @return Aoe_Scheduler_Model_Schedule
     */
    public function scheduleNow()
    {
        return $this->schedule();
    }


    /**
     * Schedule this task to be executed at a given time
     *
     * @param int $time
     * @return Aoe_Scheduler_Model_Schedule
     */
    public function schedule($time = NULL)
    {
        if (is_null($time)) {
            $time = time();
        }
        $this->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING)
            ->setCreatedAt(strftime('%Y-%m-%d %H:%M:%S', time()))
            ->setScheduledAt(strftime('%Y-%m-%d %H:%M:00', $time))
            ->save();
        return $this;
    }


    /**
     * Get job configuration
     *
     * @return Aoe_Scheduler_Model_Job_Abstract
     */
    public function getJob()
    {
        if (is_null($this->job)) {
            $this->job = Mage::getModel('aoe_scheduler/job_factory')->loadByCode($this->getJobCode());
        }
        return $this->job;
    }



    /**
     * Get start time (planned or actual)
     *
     * @return string
     */
    public function getStarttime()
    {
        $starttime = $this->getExecutedAt();
        if (empty($starttime) || $starttime == '0000-00-00 00:00:00') {
            $starttime = $this->getScheduledAt();
        }
        return $starttime;
    }


    /**
     * Get job duration
     *
     * @return bool|int time in seconds, or false
     */
    public function getDuration()
    {
        $duration = false;
        if ($this->getExecutedAt() && ($this->getExecutedAt() != '0000-00-00 00:00:00')
            && $this->getFinishedAt() && ($this->getFinishedAt() != '0000-00-00 00:00:00')
        ) {
            $duration = strtotime($this->getFinishedAt()) - strtotime($this->getExecutedAt());
        }
        return $duration;
    }

    /**
     * Is this process still alive?
     *
     * true -> alive
     * false -> dead
     * null -> we don't know because the task is running on a different server
     *
     * @return bool|null
     */
    public function isAlive()
    {
        if ($this->getStatus() == Mage_Cron_Model_Schedule::STATUS_RUNNING) {
            if (time() - strtotime($this->getLastSeen()) < 2 * 60) { // TODO: make this configurable
                return true;
            } elseif ($this->getHost() == gethostname()) {
                if ($this->checkPid()) {
                    $this
                        ->setLastSeen(strftime('%Y-%m-%d %H:%M:%S', time()))
                        ->save();
                    return true;
                } else {
                    $this->markAsDisappeared(sprintf('Process "%s" on host "%s" cannot be found anymore', $this->getPid(), $this->getHost()));
                    return false; // dead
                }
            } else {
                // we don't know because the task is running on a different server
                return null;
            }
        }
        return false;
    }

    /**
     * Mark task as disappeared
     *
     * @param string $message
     * @return void
     */
    public function markAsDisappeared($message = NULL)
    {
        if (!is_null($message)) {
            $this->setMessages($message);
        }
        $this
            ->setStatus(self::STATUS_DISAPPEARED)
            ->setFinishedAt($this->getLastSeen())
            ->save();

        $this->log(sprintf('Job "%s" (id: %s) disappeared. Message: ', $this->getJobCode(), $this->getId(), $message));
    }

    /**
     * Check if process is running (linux only)
     *
     * @return bool
     */
    public function checkPid()
    {
        $pid = intval($this->getPid());
        return $pid && file_exists('/proc/' . $pid);
    }

    /**
     * Kill this process
     *
     * @return void
     */
    public function kill()
    {

        if (!$this->checkPid()) {
            // already dead
            $this->markAsDisappeared(sprintf('Did not kill job "%s" (id: %s), because it was already dead.', $this->getJobCode(), $this->getId()));
            return true;
        }

        // let's be nice first (a.k.a. "Could you please stop running now?")
        if (posix_kill($this->getPid(), SIGINT)) {
            $this->log(sprintf('Sending SIGINT to job "%s" (id: %s)', $this->getJobCode(), $this->getId()));
        } else {
            $this->log(sprintf('Error while sending SIGINT to job "%s" (id: %s)', $this->getJobCode(), $this->getId()), Zend_Log::ERR);
        }

        // check if process terminates within 30 seconds
        $startTime = time();
        while (($waitTime = (time() - $startTime) < 30) && $this->checkPid()) {
            sleep(2);
        }

        if ($this->checkPid()) {
            // What, you're still alive? OK, time to say goodbye now. You had your chance...
            if (posix_kill($this->getPid(), SIGKILL)) {
                $this->log(sprintf('Sending SIGKILL to job "%s" (id: %s)', $this->getJobCode(), $this->getId()));
            } else {
                $this->log(sprintf('Error while sending SIGKILL to job "%s" (id: %s)', $this->getJobCode(), $this->getId()), Zend_Log::ERR);
            }
        } else {
            $this->log(sprintf('Killed job "%s" (id: %s) with SIGINT. Job terminated after %s second(s)', $this->getJobCode(), $this->getId(), $waitTime));
        }

        if ($this->checkPid()) {
            sleep(5);
            if ($this->checkPid()) {
                $this->log(sprintf('Killed job "%s" (id: %s) is still alive!', $this->getJobCode(), $this->getId()), Zend_Log::ERR);
                return; // without setting the status to "killed"
            }
        }

        $this
            ->setStatus(self::STATUS_KILLED)
            ->setFinishedAt(strftime('%Y-%m-%d %H:%M:%S', time()))
            ->save();
    }

    /**
     * Log message to configured log file (or skip)
     *
     * @param $message
     * @param null $level
     */
    protected function log($message, $level = NULL)
    {
        if ($logFile = Mage::getStoreConfig('system/cron/logFile')) {
            Mage::log($message, $level, $logFile);
        }
    }

    /**
     * Check if this is an "always" task
     *
     * @return bool
     */
    public function isAlwaysTask()
    {
        $isAlwaysTask = false;
        try {
            $isAlwaysTask = $this->getJob()->isAlwaysTask();
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $isAlwaysTask;
    }

    /**
     * Processing object before save data
     *
     * Check if there are other schedules for the same job at the same time and skip saving in this case.
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {

        if (!$this->getScheduledBy() && Mage::getSingleton('admin/session')->isLoggedIn()) {
            $this->setScheduledBy(Mage::getSingleton('admin/session')->getUser()->getId());
        }

        $collection = Mage::getModel('cron/schedule')/* @var $collection Mage_Cron_Model_Resource_Schedule_Collection */
            ->getCollection()
            ->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_PENDING)
            ->addFieldToFilter('job_code', $this->getJobCode())
            ->addFieldToFilter('scheduled_at', $this->getScheduledAt());
        if ($this->getId() !== null) {
            $collection->addFieldToFilter('schedule_id', array('neq' => $this->getId()));
        }
        $count = $collection->count();
        if ($count > 0) {
            $this->_dataSaveAllowed = false; // prevents this object from being stored to database
            $this->log(sprintf('Pending schedule for "%s" at "%s" already exists %s times. Skipping.', $this->getJobCode(), $this->getScheduledAt(), $count));
        } else {
            $this->_dataSaveAllowed = true; // allow the next object to save (because it's not reset automatically)
        }
        return parent::_beforeSave();
    }

    public function canRun($throwException=false)
    {
        if ($this->isAlwaysTask()) {
            return true;
        }
        $now = time();
        $time = strtotime($this->getScheduledAt());
        if ($time > $now) {
            // not scheduled yet
            return false;
        }
        $scheduleLifetime = Mage::getStoreConfig(Mage_Cron_Model_Observer::XML_PATH_SCHEDULE_LIFETIME) * 60;
        if ($time < $now - $scheduleLifetime) {
            $this->setStatus(Mage_Cron_Model_Schedule::STATUS_MISSED);
            $this->save();
            if ($throwException) {
                Mage::throwException(Mage::helper('cron')->__('Too late for the schedule.'));
            }
            return false;
        }
        return true;
    }

    /**
     * Process schedule
     *
     * @return $this
     */
    public function process() {
        try {
            if (!$this->canRun(true)) {
                return $this;
            }
            $this->runNow(!$this->getJob()->isAlwaysTask());
        } catch (Exception $e) {
            $this
                ->setStatus(Mage_Cron_Model_Schedule::STATUS_ERROR)
                ->setMessages($e->__toString());
            Mage::dispatchEvent('cron_' . $this->getJobCode() . '_exception', array('schedule' => $this, 'exception' => $e));
            Mage::dispatchEvent('cron_exception', array('schedule' => $this, 'exception' => $e));
            Mage::helper('aoe_scheduler')->sendErrorMail($this, $e->__toString());
        }
        $this->save();
        return $this;
    }

}