<?php

class Aoe_Scheduler_Test_Model_Job extends EcomDev_PHPUnit_Test_Case
{

    /**
     * @test
     * @return Aoe_Scheduler_Model_Job
     */
    public function checkModelClass()
    {
        $job = Mage::getModel('aoe_scheduler/job'); /* @var $job Aoe_Scheduler_Model_Job */
        $this->assertInstanceOf('Aoe_Scheduler_Model_Job', $job);
        return $job;
    }

    /**
     * @test
     */
    public function checkResourceModelClass() {
        $jobResource = Mage::getModel('aoe_scheduler/job')->getResource(); /* @var $jobResource Aoe_Scheduler_Model_Resource_Job */
        $this->assertInstanceOf('Aoe_Scheduler_Model_Resource_Job', $jobResource);

        $jobResource = Mage::getResourceModel('aoe_scheduler/job');
        $this->assertInstanceOf('Aoe_Scheduler_Model_Resource_Job', $jobResource);

        return $jobResource;
    }

    /**
     * @test
     */
    public function checkResourceCollectionModelClass() {
        $jobCollection = Mage::getModel('aoe_scheduler/job')->getCollection(); /* @var $jobCollection Aoe_Scheduler_Model_Resource_Job_Collection */
        $this->assertInstanceOf('Aoe_Scheduler_Model_Resource_Job_Collection', $jobCollection);
    }

    /**
     * @test
     * @depends checkResourceModelClass
     */
    public function checkTableName(Aoe_Scheduler_Model_Resource_Job $jobResource) {
        $this->assertEquals('cron_job', $jobResource->getMainTable());
    }

    /**
     * @test
     */
    public function checkPersistence() {

        foreach (Mage::getModel('aoe_scheduler/job')->getCollection() as $job) { /* @var $job Aoe_Scheduler_Model_Job  */
            $job->delete();
        }

        $job = Mage::getModel('aoe_scheduler/job'); /* @var $job Aoe_Scheduler_Model_Job */
        $job->setJobCode('testCode');
        $job->save();

        $reloadedJob = Mage::getModel('aoe_scheduler/job')->load($job->getId()); /* @var $reloadedJob Aoe_Scheduler_Model_Job */
        $this->assertEquals('testCode', $reloadedJob->getJobCode());

        $reloadedJob = Mage::getModel('aoe_scheduler/job')->load('testCode', 'job_code'); /* @var $reloadedJob Aoe_Scheduler_Model_Job */
        $this->assertEquals($job->getId(), $reloadedJob->getId());

        $reloadedJob->delete();

        $loadAgain = Mage::getModel('aoe_scheduler/job')->load('testCode'); /* @var $loadAgain Aoe_Scheduler_Model_Job */
        $this->assertNull($loadAgain->getJobCode());
    }

    /**
     * @test
     */
    public function checkUniqueKey() {

        foreach (Mage::getModel('aoe_scheduler/job')->getCollection() as $job) { /* @var $job Aoe_Scheduler_Model_Job  */
            $job->delete();
        }

        $job = Mage::getModel('aoe_scheduler/job'); /* @var $job Aoe_Scheduler_Model_Job */
        $job->setJobCode('testCode');
        $job->save();

        $this->setExpectedException('Zend_Db_Statement_Exception');

        $duplicateJob = Mage::getModel('aoe_scheduler/job'); /* @var $job Aoe_Scheduler_Model_Job */
        $duplicateJob->setJobCode('testCode');
        $duplicateJob->save();
    }

}

