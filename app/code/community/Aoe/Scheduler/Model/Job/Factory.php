<?php

/**
 * Job factory
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Model_Job_Factory
{

    /**
     * Jobs models sorted by priority
     *
     * @var array
     */
    protected $models = array(
        'aoe_scheduler/job_db',
        'aoe_scheduler/job_xml_global',
        'aoe_scheduler/job_xml_default',
    );

    /**
     * Load job
     *
     * @param $jobCode
     * @param bool $xmlOnly
     * @return Aoe_Scheduler_Model_Job_Abstract|false
     */
    public function loadByCode($jobCode, $xmlOnly=false)
    {
        $models = $this->models;
        if ($xmlOnly) {
            array_shift($models);
        }
        foreach ($models as $model) {
            $job = Mage::getModel($model); /* @var $job Aoe_Scheduler_Model_Job_Abstract */
            $job->loadByCode($jobCode);
            if ($job->getJobCode()) {
                return $job;
            }
        }
        return false;
    }

    /**
     * Get all jobs
     *
     * @param array $whitelist
     * @param array $blacklist
     *
     * @return Varien_Data_Collection
     */
    public function getAllJobs(array $whitelist = array(), $blacklist = array())
    {
        $whitelist = array_filter(array_map('trim', $whitelist));
        $blacklist = array_filter(array_map('trim', $blacklist));

        $jobs = Mage::getModel('aoe_scheduler/job_collection'); /* @var $jobs Aoe_Scheduler_Model_Job_Collection */
        foreach ($this->models as $model) {
            $jobCollection = Mage::getModel($model)->getCollection();
            foreach ($jobCollection as $job) { /* @var $job Aoe_Scheduler_Model_Job_Abstract */
                $jobCode = $job->getJobCode();
                if(count($whitelist) && !in_array($jobCode, $whitelist)) {
                    continue;
                }
                if(count($blacklist) && in_array($jobCode, $blacklist)) {
                    continue;
                }
                if (!$jobs->getItemById($jobCode)) {
                    $jobs->addItem($job);
                }
            }
        }
        return $jobs;
    }

}
