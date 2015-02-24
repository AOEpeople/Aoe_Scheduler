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
        'aoe_scheduler/job_xml',
    );

    /**
     * Load job
     *
     * @param $jobCode
     * @return Aoe_Scheduler_Model_Job_Abstract|false
     */
    public function loadByCode($jobCode)
    {
        foreach ($this->models as $model) {
            /* @var Aoe_Scheduler_Model_Job_Abstract $job */
            $job = Mage::getModel($model);
            $job->loadByCode($jobCode);
            if ($job->getJobCode()) {
                return $job;
            }
        }
        return false;
    }

    /**
     * Load all jobs by code
     *
     * @param $jobCode
     * @return Aoe_Scheduler_Model_Job_Abstract[]
     */
    public function loadAllByCode($jobCode, $afterModel = null)
    {
        $jobs = array();

        if(!empty($jobCode)) {
            $models = array_values(array_unique($this->models));
            if($afterModel && in_array($afterModel, $models)) {
                $offset = array_search($afterModel, $models) + 1;
                if(count($models) > $offset) {
                    $models = array_slice($models, $offset);
                } else {
                    $models = array();
                }
            }
            foreach ($models as $model) {
                /* @var Aoe_Scheduler_Model_Job_Abstract $job */
                $job = Mage::getModel($model);
                $job->loadByCode($jobCode);
                if ($job->getJobCode()) {
                    $jobs[] = $job;
                }
            }
        }

        return $jobs;
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
