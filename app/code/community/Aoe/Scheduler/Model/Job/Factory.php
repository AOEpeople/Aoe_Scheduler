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
     * @return Aoe_Scheduler_Model_Job_Abstract|false
     */
    public function loadByCode($jobCode)
    {
        foreach ($this->models as $model) {
            $job = Mage::getModel($model); /* @var $job Aoe_Scheduler_Model_Job_Abstract */
            $job->loadByCode($jobCode);
            if ($job->getJobCode()) {
                return $job;
            }
        }
        return false;
    }

    /**
     * @return Aoe_Scheduler_Model_Job_Abstract[]
     */
    public function getAllJobs()
    {
        $jobs = array();
        foreach ($this->models as $model) {
            foreach (Mage::getModel($model)->getCollection() as $job) { /* @var $job Aoe_Scheduler_Model_Job_Abstract */
                $jobCode = $job->getJobCode();
                if (!array_key_exists($jobCode, $jobs)) {
                    $jobs[$jobCode] = $job;
                }
            }
        }
        return $jobs;
    }

}