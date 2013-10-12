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
 */
class Aoe_Scheduler_Model_Schedule extends Mage_Cron_Model_Schedule {

    CONST STATUS_KILLED = 'killed';
    CONST STATUS_DISAPPEARED = 'disappeared';

	/**
	 * @var Aoe_Scheduler_Model_Configuration
	 */
	protected $_jobConfiguration;

    /**
     * @var bool
     */
    protected $jobWasLocked = false;


	/**
	 * Run this task now
	 *
	 * @param bool $tryLockJob
	 * @return Aoe_Scheduler_Model_Schedule
	 */
	public function runNow($tryLockJob=true) {
		$modelCallback = $this->getJobConfiguration()->getModel();

		if (!$this->getCreatedAt()) {
			$this->schedule();
		}

		if (!preg_match(Mage_Cron_Model_Observer::REGEX_RUN_MODEL, $modelCallback, $run)) {
			Mage::throwException(Mage::helper('cron')->__('Invalid model/method definition, expecting "model/class::method".'));
		}
		if (!($model = Mage::getModel($run[1])) || !method_exists($model, $run[2])) {
			Mage::throwException(Mage::helper('cron')->__('Invalid callback: %s::%s does not exist', $run[1], $run[2]));
		}
		$callback = array($model, $run[2]);

		if (empty($callback)) {
			Mage::throwException(Mage::helper('cron')->__('No callbacks found'));
		}

		// lock job requires the record to be saved and having status Mage_Cron_Model_Schedule::STATUS_PENDING
		// workaround could be to do this: $this->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING)->save();
		$this->jobWasLocked = false;
		if ($tryLockJob && !$this->tryLockJob()) {
			// another cron started this job intermittently, so skip it
			$this->jobWasLocked = true;
			return $this;
		}

		$this->setExecutedAt(strftime('%Y-%m-%d %H:%M:%S', time()));
        $this->setStatus(Mage_Cron_Model_Schedule::STATUS_RUNNING);
        $this->setHost(gethostname());
        $this->setPid(getmypid());
        $this->save();

        Mage::dispatchEvent('cron_' . $this->getJobCode() . '_before', array('schedule' => $this));
        Mage::dispatchEvent('cron_before', array('schedule' => $this));

		$messages = call_user_func_array($callback, array($this));

		// added by Fabrizio to also save messages when no exception was thrown
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
	public function getJobWasLocked() {
		return $this->jobWasLocked;
	}



	/**
	 * Schedule this task to be executed as soon as possible
	 *
	 * @deprecated use Aoe_Scheduler_Model_Schedule::schedule() instead
	 * @return Aoe_Scheduler_Model_Schedule
	 */
	public function scheduleNow() {
		return $this->schedule();
	}



	/**
	 * Schedule this task to be executed at a given time
	 *
	 * @param int $time
	 * @return Aoe_Scheduler_Model_Schedule
	 */
	public function schedule($time=NULL) {
		if (is_null($time)) {
			$time = time();
		}
		$this->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING)
			->setCreatedAt(strftime('%Y-%m-%d %H:%M:%S', time()))
			->setScheduledAt(strftime('%Y-%m-%d %H:%M:%S', $time))
			->save();
		return $this;
	}



	/**
	 * Get job configuration
	 *
	 * @return Aoe_Scheduler_Model_Configuration
	 */
	public function getJobConfiguration() {
		if (is_null($this->_jobConfiguration)) {
			$this->_jobConfiguration = Mage::getModel('aoe_scheduler/configuration')->loadByCode($this->getJobCode());
		}
		return $this->_jobConfiguration;
	}



	/**
	 * Get start time (planned or actual)
	 *
	 * @return string
	 */
	public function getStarttime() {
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
	public function getDuration() {
		$duration = false;
		if ($this->getExecutedAt() && ($this->getExecutedAt() != '0000-00-00 00:00:00')
			&& $this->getFinishedAt() && ($this->getFinishedAt() != '0000-00-00 00:00:00')) {
			$duration = strtotime($this->getFinishedAt()) - strtotime($this->getExecutedAt());
		}
		return $duration;
	}

    /**
     * Is this process still alive?
     *
     * @return bool
     */
    public function isAlive() {
        if ($this->getStatus() == Mage_Cron_Model_Schedule::STATUS_RUNNING) {
            if (time() - strtotime($this->getLastSeen()) < 2 * 60) { // TODO: make this configurable
                return true;
            } elseif ($this->getHost() == gethostname()) {
                if ($this->checkPid()) {
                    $this->setLastSeen(strftime('%Y-%m-%d %H:%M:%S', time()))->save();
                    return true;
                } else {
                    $this->setStatus(self::STATUS_DISAPPEARED)->save();
                    if ($logFile = Mage::getStoreConfig('system/cron/logFile')) {
                        Mage::log(sprintf('Job "%s" (id: %s) disappeared', $this->getJobCode(), $this->getId()), null, $logFile);
                    }
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
     * Check if process is running (linux only)
     *
     * @return bool
     */
    public function checkPid() {
        $pid = intval($this->getPid());
        return $pid && file_exists('/proc/' . $pid);
    }

    /**
     * Kill this process
     *
     * @return void
     */
    public function kill() {

        posix_kill($this->getPid(), SIGINT); // let's be nice first (a.k.a. "Could you please stop running now?")

        // check if process terminates within 60 seconds
        $startTime = time();
        while (($waitTime = (time() - $startTime) < 60) && $this->checkPid()) {
            sleep(2);
        }

        if ($this->checkPid()) {
            // What, you're still alive? OK, time to say goodbye now. You had your chance...
            posix_kill($this->getPid(), SIGKILL);
            if ($logFile = Mage::getStoreConfig('system/cron/logFile')) {
                Mage::log(sprintf('Killed job "%s" (id: %s) with SIGKILL', $this->getJobCode(), $this->getId()), null, $logFile);
            }
        } else {
            if ($logFile = Mage::getStoreConfig('system/cron/logFile')) {
                Mage::log(sprintf('Killed job "%s" (id: %s) with SIGINT. Job terminated after %s second(s)', $this->getJobCode(), $this->getId(), $waitTime), null, $logFile);
            }
        }

        $this->setStatus(self::STATUS_KILLED)->save();
    }


}