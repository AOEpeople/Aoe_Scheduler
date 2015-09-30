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
 * @method $this setMessages()
 * @method $this setExecutedAt()
 * @method $this setCreatedAt()
 * @method $this setScheduledAt()
 * @method $this setStatus()
 * @method $this setFinishedAt()
 * @method $this setParameters()
 * @method $this setEta()
 * @method string getEta()
 * @method $this setHost()
 * @method string getHost()
 * @method $this setPid()
 * @method string getPid()
 * @method $this setProgressMessage()
 * @method string getProgressMessage()
 * @method string getLastSeen()
 * @method $this setLastSeen()
 * @method string getScheduledBy()
 * @method $this setScheduledBy($scheduledBy)
 * @method string getScheduledReason()
 * @method $this setScheduledReason($scheduledReason)
 * @method string getKillRequest()
 * @method $this setKillRequest($killRequest)
 */
class Aoe_Scheduler_Model_Schedule extends Mage_Cron_Model_Schedule
{

    const STATUS_KILLED = 'killed';
    const STATUS_DISAPPEARED = 'gone';
    const STATUS_DIDNTDOANYTHING = 'nothing';

    const STATUS_SKIP_LOCKED = 'locked';
    const STATUS_SKIP_OTHERJOBRUNNING = 'other_job_running';

    const STATUS_DIED = 'died'; // note that died != killed

    const REASON_RUNNOW_WEB = 'run_now_web';
    const REASON_SCHEDULENOW_WEB = 'schedule_now_web';
    const REASON_RUNNOW_CLI = 'run_now_cli';
    const REASON_SCHEDULENOW_CLI = 'schedule_now_cli';
    const REASON_RUNNOW_API = 'run_now_api';
    const REASON_SCHEDULENOW_API = 'schedule_now_api';
    const REASON_GENERATESCHEDULES = 'generate_schedules';
    const REASON_DEPENDENCY_ALL = 'dependency_all';
    const REASON_DEPENDENCY_SUCCESS = 'dependency_success';
    const REASON_DEPENDENCY_FAILURE = 'dependency_failure';

    /**
     * Event name prefix for events that are dispatched by this class
     *
     * @var string
     */
    protected $_eventPrefix = 'aoe_scheduler_schedule';

    /**
     * Event parameter name that references this object in an event
     *
     * In an observer method you can use $observer->getData('schedule') or $observer->getData('data_object') to get this object
     *
     * @var string
     */
    protected $_eventObject = 'schedule';

    /**
     * @var Aoe_Scheduler_Model_Job
     */
    protected $job;

    /**
     * @var bool
     */
    protected $jobWasLocked = false;

    /**
     * Placeholder to keep track of active redirect buffer.
     *
     * @var bool
     */
    protected $_redirect = false;

    /**
     * The buffer will be flushed after any output call which causes
     * the buffer's length to equal or exceed this value.
     *
     * Prior to PHP 5.4.0, the value 1 set the chunk size to 4096 bytes.
     */
    protected $_redirectOutputHandlerChunkSize = 100; // bytes

    /**
     * Backup of the original error settings
     *
     * @var array
     */
    protected $errorSettingsBackup = array();


    /**
     * Initialize from job
     *
     * @param Aoe_Scheduler_Model_Job $job
     * @return $this
     */
    public function initializeFromJob(Aoe_Scheduler_Model_Job $job)
    {
        $this->setJobCode($job->getJobCode());
        $this->setCronExpr($job->getCronExpression());
        $this->setStatus(Aoe_Scheduler_Model_Schedule::STATUS_PENDING);
        return $this;
    }


    /**
     * Run this task now
     *
     * @param bool $tryLockJob
     * @param bool $forceRun
     * @return Aoe_Scheduler_Model_Schedule
     */
    public function runNow($tryLockJob = true, $forceRun = false)
    {
        // if this schedule doesn't exist yet, create it
        if (!$this->getCreatedAt()) {
            $this->schedule();
        }

        if (!$forceRun) {
            // lock job (see below) prevents the exact same schedule from being executed from more than one process (or server)
            // the following check will prevent multiple schedules of the same type to be run in parallel
            $processManager = Mage::getModel('aoe_scheduler/processManager'); /* @var $processManager Aoe_Scheduler_Model_ProcessManager */
            if ($processManager->isJobCodeRunning($this->getJobCode(), $this->getId())) {
                $this->setStatus(self::STATUS_SKIP_OTHERJOBRUNNING);
                $this->log(sprintf('Job "%s" (id: %s) will not be executed because there is already another process with the same job code running. Skipping.', $this->getJobCode(), $this->getId()));
                return $this;
            }
        }

        // lock job requires the record to be saved and having status Aoe_Scheduler_Model_Schedule::STATUS_PENDING
        // workaround could be to do this: $this->setStatus(Aoe_Scheduler_Model_Schedule::STATUS_PENDING)->save();
        $this->jobWasLocked = false;
        if ($tryLockJob && !$this->tryLockJob()) {
            $this->setStatus(self::STATUS_SKIP_LOCKED);
            // another cron started this job intermittently, so skip it
            $this->jobWasLocked = true;
            $this->log(sprintf('Job "%s" (id: %s) is locked. Skipping.', $this->getJobCode(), $this->getId()));
            return $this;
        }

        try {
            $job = $this->getJob();

            if (!$job) {
                Mage::throwException(sprintf("Could not create job with jobCode '%s'", $this->getJobCode()));
            }

            $startTime = time();
            $this
                ->setExecutedAt(strftime('%Y-%m-%d %H:%M:%S', $startTime))
                ->setLastSeen(strftime('%Y-%m-%d %H:%M:%S', $startTime))
                ->setStatus(Aoe_Scheduler_Model_Schedule::STATUS_RUNNING)
                ->setHost(gethostname())
                ->setPid(getmypid())
                ->save();

            Aoe_Scheduler_Helper_GracefulDead::configure();

            Mage::register('currently_running_schedule', $this);

            Mage::dispatchEvent('cron_' . $this->getJobCode() . '_before', array('schedule' => $this));
            Mage::dispatchEvent('cron_before', array('schedule' => $this));

            Mage::unregister('current_cron_task');
            Mage::register('current_cron_task', $this);

            $this->log('Start: ' . $this->getJobCode());

            $this->_startBufferToMessages();
            $this->jobErrorContext();

            $callback = $job->getCallback();

            try {
                // this is where the magic happens
                $messages = call_user_func_array($callback, array($this));

                $this->restoreErrorContext();
                $this->_stopBufferToMessages();
            } catch (Exception $e) {
                $this->restoreErrorContext();
                $this->_stopBufferToMessages();
                throw $e;
            }

            $this->log('Stop: ' . $this->getJobCode());

            if (!empty($messages)) {
                if (is_object($messages)) {
                    $messages = get_class($messages);
                } elseif (!is_scalar($messages)) {
                    $messages = var_export($messages, 1);
                }
                $this->addMessages(PHP_EOL . '---RETURN_VALUE---' . PHP_EOL . $messages);
            }

            // schedules can report an error state by returning a string that starts with "ERROR:"
            if ((is_string($messages) && strtoupper(substr($messages, 0, 6)) == 'ERROR:') || $this->getStatus() === Aoe_Scheduler_Model_Schedule::STATUS_ERROR) {
                $this->setStatus(Aoe_Scheduler_Model_Schedule::STATUS_ERROR);
                Mage::helper('aoe_scheduler')->sendErrorMail($this, $messages);
                Mage::dispatchEvent('cron_' . $this->getJobCode() . '_after_error', array('schedule' => $this));
                Mage::dispatchEvent('cron_after_error', array('schedule' => $this));
            } elseif ((is_string($messages) && strtoupper(substr($messages, 0, 7)) == 'NOTHING') || $this->getStatus() === Aoe_Scheduler_Model_Schedule::STATUS_DIDNTDOANYTHING) {
                $this->setStatus(Aoe_Scheduler_Model_Schedule::STATUS_DIDNTDOANYTHING);
                Mage::dispatchEvent('cron_' . $this->getJobCode() . '_after_nothing', array('schedule' => $this));
                Mage::dispatchEvent('cron_after_nothing', array('schedule' => $this));
            } else {
                $this->setStatus(Aoe_Scheduler_Model_Schedule::STATUS_SUCCESS);
                Mage::dispatchEvent('cron_' . $this->getJobCode() . '_after_success', array('schedule' => $this));
                Mage::dispatchEvent('cron_after_success', array('schedule' => $this));
            }

        } catch (Exception $e) {
            $this->setStatus(Aoe_Scheduler_Model_Schedule::STATUS_ERROR);
            $this->addMessages(PHP_EOL . '---EXCEPTION---' . PHP_EOL . $e->__toString());
            Mage::dispatchEvent('cron_' . $this->getJobCode() . '_exception', array('schedule' => $this, 'exception' => $e));
            Mage::dispatchEvent('cron_exception', array('schedule' => $this, 'exception' => $e));
            Mage::helper('aoe_scheduler')->sendErrorMail($this, $e->__toString());
        }

        $this->setFinishedAt(strftime('%Y-%m-%d %H:%M:%S', time()));
        Mage::dispatchEvent('cron_' . $this->getJobCode() . '_after', array('schedule' => $this));
        Mage::dispatchEvent('cron_after', array('schedule' => $this));

        $this->save();
        Mage::unregister('currently_running_schedule');

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
    public function schedule($time = null)
    {
        if (is_null($time)) {
            $time = time();
        }
        $this->setStatus(Aoe_Scheduler_Model_Schedule::STATUS_PENDING)
            ->setCreatedAt(strftime('%Y-%m-%d %H:%M:%S', time()))
            ->setScheduledAt(strftime('%Y-%m-%d %H:%M:00', $time))
            ->save();
        return $this;
    }


    /**
     * Get job configuration
     *
     * @return Aoe_Scheduler_Model_Job
     */
    public function getJob()
    {
        if (is_null($this->job)) {
            $this->job = Mage::getModel('aoe_scheduler/job')->load($this->getJobCode());
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
     * Get job duration.
     *
     * @return bool|int time in seconds, or false
     */
    public function getDuration()
    {
        $duration = false;
        if ($this->getExecutedAt() && ($this->getExecutedAt() != '0000-00-00 00:00:00')) {
            if ($this->getFinishedAt() && ($this->getFinishedAt() != '0000-00-00 00:00:00')) {
                $time = strtotime($this->getFinishedAt());
            } elseif ($this->getStatus() == Aoe_Scheduler_Model_Schedule::STATUS_RUNNING) {
                $time = time();
            } else {
                // Mage::throwException('No finish time found, but the job is not running');
                return false;
            }
            $duration = $time - strtotime($this->getExecutedAt());
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
        if ($this->getStatus() == Aoe_Scheduler_Model_Schedule::STATUS_RUNNING) {
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
    public function markAsDisappeared($message = null)
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
     * Request kill
     *
     * @param int $time
     * @param string $message
     * @return $this
     */
    public function requestKill($time = null, $message = null)
    {
        if (is_null($time)) {
            $time = time();
        }
        if (!is_null($message)) {
            $this->addMessages($message);
        }
        $this->setKillRequest(strftime('%Y-%m-%d %H:%M:%S', $time))
           ->save();
        return $this;
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
            return;
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
    protected function log($message, $level = null)
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
            $job = $this->getJob();
            $isAlwaysTask = $job && $job->isAlwaysTask();
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
        if (!$this->getScheduledBy() && php_sapi_name() !== 'cli' && Mage::getSingleton('admin/session')->isLoggedIn()) {
            $this->setScheduledBy(Mage::getSingleton('admin/session')->getUser()->getId());
        }

        $collection = Mage::getModel('cron/schedule')/* @var $collection Mage_Cron_Model_Resource_Schedule_Collection */
            ->getCollection()
            ->addFieldToFilter('status', Aoe_Scheduler_Model_Schedule::STATUS_PENDING)
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

    /**
     * Check if this schedule can be run
     *
     * @param bool $throwException
     * @return bool
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    public function canRun($throwException = false)
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
            $this->setStatus(Aoe_Scheduler_Model_Schedule::STATUS_MISSED);
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
    public function process()
    {
        if (!$this->canRun(false)) {
            return $this;
        }
        $this->runNow(!$this->isAlwaysTask());
        return $this;
    }

    /**
     * Get parameters (and fallback to job)
     *
     * @return mixed
     */
    public function getParameters()
    {
        if ($this->getData('parameters')) {
            return $this->getData('parameters');
        }
        // fallback to job
        $job = $this->getJob();
        if ($job) {
            return $job->getParameters();
        } else {
            return false;
        }
    }

    /**
     * Switch the job error context
     */
    protected function jobErrorContext()
    {
        if (!Mage::getStoreConfigFlag('system/cron/enableErrorLog')) {
            return;
        }

        $settings = array(
            'error_reporting' => intval(Mage::getStoreConfig('system/cron/errorLevel')),
            'log_errors' => true,
            'display_errors' => true,
            'error_log' => $this->getErrorLogFile()
        );

        restore_error_handler();
        // (doesn't work for PHP 5.3) set_error_handler(null); // switch to PHP default error handling

        if (!is_dir(dirname($settings['error_log']))) {
            mkdir(dirname($settings['error_log']), 0775, true);
        }

        foreach ($settings as $key => $value) {
            // backup original settings first
            $this->errorSettingsBackup[$key] = ini_get($key);
            // set new value
            ini_set($key, $value);
        }
    }

    /**
     * Restore the original error context
     */
    protected function restoreErrorContext()
    {
        if (!Mage::getStoreConfigFlag('system/cron/enableErrorLog')) {
            return;
        }

        restore_error_handler();
        foreach ($this->errorSettingsBackup as $key => $value) {
            ini_set($key, $value);
        }
    }

    /**
     * Get error log filename
     *
     * @return string
     */
    public function getErrorLogFile()
    {
        $replace = array(
            '###PID###' => $this->getPid(),
            '###ID###' => $this->getId(),
            '###JOBCODE###' => $this->getJobCode()
        );
        $basedir = Mage::getBaseDir('log') . DS . 'cron' . DS;
        return $basedir . str_replace(array_keys($replace), array_values($replace), Mage::getStoreConfig('system/cron/errorLogFilename'));
    }

    /**
     * Redirect all output to the messages field of this Schedule.
     *
     * We use ob_start with `_addBufferToMessages` to redirect the output.
     *
     * @return $this
     */
    protected function _startBufferToMessages()
    {
        if (!Mage::getStoreConfigFlag('system/cron/enableJobOutputBuffer')) {
            return $this;
        }

        if ($this->_redirect) {
            return $this;
        }

        $this->addMessages('---START---' . PHP_EOL);

        ob_start(
            array($this, '_addBufferToMessages'),
            $this->_redirectOutputHandlerChunkSize
        );

        $this->_redirect = true;
    }

    /**
     * Stop redirecting all output to the messages field of this Schedule.
     *
     * We use ob_end_flush to stop redirecting the output.
     *
     * @return $this
     */
    protected function _stopBufferToMessages()
    {
        if (!Mage::getStoreConfigFlag('system/cron/enableJobOutputBuffer')) {
            return $this;
        }

        if (!$this->_redirect) {
            return $this;
        }

        ob_end_flush();
        $this->addMessages('---END---' . PHP_EOL);

        $this->_redirect = false;
    }

    /**
     * Used as callback function to redirect the output buffer
     * directly into the messages field of this schedule.
     *
     * @param $buffer
     *
     * @return string
     */
    public function _addBufferToMessages($buffer)
    {
        $this->addMessages($buffer)
            ->saveMessages(); // Save the directly to the schedule record.

        return $buffer;
    }

    /**
     * Append data to the current messages field.
     *
     * @param $messages
     *
     * @return $this
     */
    public function addMessages($messages)
    {
        $this->setMessages($this->getMessages() . $messages);

        return $this;
    }

    /**
     * Save the messages directly to the schedule record.
     *
     * If the `messages` field was not updated in the database,
     * check if this is because of `data truncation` and fix the message length.
     *
     * @return $this
     */
    public function saveMessages()
    {
        if (!$this->getId()) {
            return $this->save();
        }

        $connection = Mage::getSingleton('core/resource')
            ->getConnection('core_write');

        $count = $connection
            ->update(
                $this->getResource()->getMainTable(),
                array('messages' => $this->getMessages()),
                array('schedule_id = ?' => $this->getId())
            );

        if (!$count) {
            /**
             * Check if the row was not updated because of data truncation.
             */
            $warning = $this->_getPdoWarning($connection->getConnection());
            if ($warning && $warning->Code = 1265) {
                $maxLength = strlen($this->getMessages()) - 5000;
                $this->setMessages($warning->Level . ': ' .
                    str_replace(' at row 1', '.', $warning->Message) . PHP_EOL . PHP_EOL .
                    '...' . substr($this->getMessages(), -$maxLength));
            }
        }

        return $this;
    }

    /**
     * Retrieve the last PDO warning.
     *
     * @param PDO $pdo
     * @return mixed
     */
    protected function _getPdoWarning(PDO $pdo)
    {
        $originalErrorMode = $pdo->getAttribute(PDO::ATTR_ERRMODE);

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

        $stm = $pdo->query('SHOW WARNINGS');

        $pdo->setAttribute(PDO::ATTR_ERRMODE, $originalErrorMode);

        return $stm->fetchObject();
    }

    /**
     * Bypass parent's setCronExpr is the expression is "always"
     * This will break trySchedule, but always tasks will never be tried to scheduled anyway
     *
     * @param $expr
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function setCronExpr($expr)
    {
        if ($expr == 'always') {
            $this->setData('cron_expr', $expr);
        } else {
            parent::setCronExpr($expr);
        }
        return $this;
    }

    /**
     * Gets statuses that are currently in the scheduler table
     *
     * @return array
     */
    public function getStatuses()
    {
        $schedules = clone $this->getCollection()
            ->setOrder('status', Zend_Db_Select::SQL_ASC);
        $schedules->getSelect()
            ->group('status')
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns('status');
        $statuses = $schedules->getConnection()
            ->fetchCol($schedules->getSelect());
        $statusArray = array();
        foreach ($statuses as $status) {
            $statusArray[$status] = $status;
        }
        return $statusArray;
    }
}
