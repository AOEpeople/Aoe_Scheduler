<?php

/**
 * Helper
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Helper_Data extends Mage_Core_Helper_Abstract
{

    const XML_PATH_MAX_RUNNING_TIME = 'system/cron/max_running_time';
    const XML_PATH_EMAIL_TEMPLATE = 'system/cron/error_email_template';
    const XML_PATH_EMAIL_IDENTITY = 'system/cron/error_email_identity';
    const XML_PATH_EMAIL_RECIPIENT = 'system/cron/error_email';

    protected $groupsToJobsMap = null;

    /**
     * Explodes a string and trims all values for whitespace in the ends.
     * If $onlyNonEmptyValues is set, then all blank ('') values are removed.
     *
     * @see t3lib_div::trimExplode() in TYPO3
     * @param $delim
     * @param string $string
     * @param bool $removeEmptyValues If set, all empty values will be removed in output
     * @return array Exploded values
     */
    public function trimExplode($delim, $string, $removeEmptyValues = false)
    {
        $explodedValues = explode($delim, $string);

        $result = array_map('trim', $explodedValues);

        if ($removeEmptyValues) {
            $temp = array();
            foreach ($result as $value) {
                if ($value !== '') {
                    $temp[] = $value;
                }
            }
            $result = $temp;
        }

        return $result;
    }

    /**
     * Decorate status values
     *
     * @param $status
     * @return string
     */
    public function decorateStatus($status)
    {
        switch ($status) {
            case Aoe_Scheduler_Model_Schedule::STATUS_SUCCESS:
            case Aoe_Scheduler_Model_Schedule::STATUS_DIDNTDOANYTHING:
                $result = '<span class="bar-green"><span>' . $status . '</span></span>';
                break;
            case Aoe_Scheduler_Model_Schedule::STATUS_PENDING:
                $result = '<span class="bar-lightgray"><span>' . $status . '</span></span>';
                break;
            case Aoe_Scheduler_Model_Schedule::STATUS_RUNNING:
                $result = '<span class="bar-yellow"><span>' . $status . '</span></span>';
                break;
            case Aoe_Scheduler_Model_Schedule::STATUS_SKIP_OTHERJOBRUNNING:
            case Aoe_Scheduler_Model_Schedule::STATUS_SKIP_LOCKED:
            case Aoe_Scheduler_Model_Schedule::STATUS_MISSED:
                $result = '<span class="bar-orange"><span>' . $status . '</span></span>';
                break;
            case Aoe_Scheduler_Model_Schedule::STATUS_ERROR:
            case Aoe_Scheduler_Model_Schedule::STATUS_DISAPPEARED:
            case Aoe_Scheduler_Model_Schedule::STATUS_KILLED:
                $result = '<span class="bar-red"><span>' . $status . '</span></span>';
                break;
            default:
                $result = $status;
                break;
        }
        return $result;
    }

    /**
     * Wrapper for decorateTime to be used a frame_callback to avoid that additional parameters
     * conflict with the method's optional ones
     *
     * @param string $value
     * @return string
     */
    public function decorateTimeFrameCallBack($value)
    {
        return $this->decorateTime($value, false, null);
    }

    /**
     * Decorate time values
     *
     * @param string $value
     * @param bool $echoToday if true "Today" will be added
     * @param string $dateFormat make sure Y-m-d is in it, if you want to have it replaced
     * @return string
     */
    public function decorateTime($value, $echoToday = false, $dateFormat = null)
    {
        if (empty($value) || $value == '0000-00-00 00:00:00') {
            $value = '';
        } else {
            $value = Mage::getModel('core/date')->date($dateFormat, $value);
            $replace = array(
                Mage::getModel('core/date')->date('Y-m-d ', time()) => $echoToday ? Mage::helper('aoe_scheduler')->__('Today') . ', ' : '', // today
                Mage::getModel('core/date')->date('Y-m-d ', strtotime('+1 day')) => Mage::helper('aoe_scheduler')->__('Tomorrow') . ', ',
                Mage::getModel('core/date')->date('Y-m-d ', strtotime('-1 day')) => Mage::helper('aoe_scheduler')->__('Yesterday') . ', ',
            );
            $value = str_replace(array_keys($replace), array_values($replace), $value);
        }
        return $value;
    }

    /**
     * Get last heartbeat
     */
    public function getLastHeartbeat()
    {
        return $this->getLastExecutionTime('aoescheduler_heartbeat');
    }

    /**
     * Get last execution time
     *
     * @param $jobCode
     * @return bool
     */
    public function getLastExecutionTime($jobCode)
    {
        if ($this->isDisabled($jobCode)) {
            return false;
        }
        $schedules = Mage::getModel('cron/schedule')->getCollection(); /* @var $schedules Mage_Cron_Model_Mysql4_Schedule_Collection */
        $schedules->getSelect()->limit(1)->order('executed_at DESC');
        $schedules->addFieldToFilter('status', Aoe_Scheduler_Model_Schedule::STATUS_SUCCESS);
        $schedules->addFieldToFilter('job_code', $jobCode);
        $schedules->load();
        if (count($schedules) == 0) {
            return false;
        }
        $executedAt = $schedules->getFirstItem()->getExecutedAt();
        $value = Mage::getModel('core/date')->date(null, $executedAt);
        return $value;
    }

    /**
     * Diff between to times;
     *
     * @param $time1
     * @param $time2
     * @return int
     */
    public function dateDiff($time1, $time2 = null)
    {
        if (is_null($time2)) {
            $time2 = Mage::getModel('core/date')->date();
        }
        $time1 = strtotime($time1);
        $time2 = strtotime($time2);
        return $time2 - $time1;
    }

    /**
     * Check if job code is disabled in configuration
     *
     * @param $jobCode
     * @return bool
     */
    public function isDisabled($jobCode)
    {
        /* @var $job Aoe_Scheduler_Model_Job */
        $job = Mage::getModel('aoe_scheduler/job')->load($jobCode);
        return ($job->getJobCode() && !$job->getIsActive());
    }

    /**
     * Check if a job matches the group include/exclude lists
     *
     * @param $jobCode
     * @param array $include
     * @param array $exclude
     * @return mixed
     */
    public function matchesIncludeExclude($jobCode, array $include, array $exclude)
    {
        $include = array_filter(array_map('trim', $include));
        $exclude = array_filter(array_map('trim', $exclude));

        sort($include);
        sort($exclude);

        $key = $jobCode . '|' . implode(',', $include) . '|' . implode(',', $exclude);
        static $cache = array();
        if (!isset($cache[$key])) {
            if (count($include) == 0 && count($exclude) == 0) {
                $cache[$key] = true;
            } else {
                $cache[$key] = true;
                /* @var $job Aoe_Scheduler_Model_Job */
                $job = Mage::getModel('aoe_scheduler/job')->load($jobCode);
                $groups = $this->trimExplode(',', $job->getGroups(), true);
                if (count($include) > 0) {
                    $cache[$key] = (count(array_intersect($groups, $include)) > 0);
                }
                if (count($exclude) > 0) {
                    if (count(array_intersect($groups, $exclude)) > 0) {
                        $cache[$key] = false;
                    }
                }
            }

        }
        return $cache[$key];
    }

    public function getGroupsToJobsMap($forceRebuild = false)
    {
        if ($this->groupsToJobsMap === null || $forceRebuild) {
            $map = array();

            /* @var $jobs Aoe_Scheduler_Model_Resource_Job_Collection */
            $jobs = Mage::getSingleton('aoe_scheduler/job')->getCollection();
            foreach ($jobs as $job) {
                /* @var Aoe_Scheduler_Model_Job $job */
                $groups = $this->trimExplode(',', $job->getGroups(), true);
                foreach ($groups as $group) {
                    $map[$group][] = $job->getJobCode();
                }
            }

            $this->groupsToJobsMap = $map;
        }

        return $this->groupsToJobsMap;
    }

    public function addGroupJobs(array $jobs, array $groups)
    {
        $map = $this->getGroupsToJobsMap();

        foreach ($groups as $group) {
            if (isset($map[$group])) {
                foreach ($map[$group] as $jobCode) {
                    $jobs[] = $jobCode;
                }
            }
        }

        return $jobs;
    }

    /**
     * Send error mail
     *
     * @param Aoe_Scheduler_Model_Schedule $schedule
     * @param $error
     * @return void
     */
    public function sendErrorMail(Aoe_Scheduler_Model_Schedule $schedule, $error)
    {
        if (!Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT)) {
            return;
        }

        $translate = Mage::getSingleton('core/translate'); /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);

        $emailTemplate = Mage::getModel('core/email_template'); /* @var $emailTemplate Mage_Core_Model_Email_Template */
        $emailTemplate->setDesignConfig(array('area' => 'backend'));
        $emailTemplate->sendTransactional(
            Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE),
            Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY),
            Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT),
            null,
            array('error' => $error, 'schedule' => $schedule)
        );

        $translate->setTranslateInline(true);
    }

    /**
     * Get callback from runModel
     *
     * @param $runModel
     * @return array
     */
    public function getCallBack($runModel)
    {
        if (!preg_match(Mage_Cron_Model_Observer::REGEX_RUN_MODEL, (string) $runModel, $run)) {
            Mage::throwException(Mage::helper('cron')->__('Invalid model/method definition, expecting "model/class::method", got "' . $runModel . '" instead.'));
        }
        if (!($model = Mage::getModel($run[1]))) {
            Mage::throwException(Mage::helper('cron')->__('Invalid callback: Model for %s::%s does not exist', $run[1], $run[2]));
        }
        if (!method_exists($model, $run[2])) {
            Mage::throwException(Mage::helper('cron')->__('Invalid callback: Method for %s::%s does not exist', $run[1], $run[2]));
        }
        $callback = array($model, $run[2]);
        return $callback;
    }

    /**
     * Validate cron expression
     *
     * @param $cronExpression
     * @return bool
     */
    public function validateCronExpression($cronExpression)
    {
        try {
            $schedule = Mage::getModel('cron/schedule');
            /* @var $schedule Mage_Cron_Model_Schedule */
            $schedule->setCronExpr($cronExpression);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
}
