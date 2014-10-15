<?php

class Aoe_Scheduler_Test_Helper_Data extends EcomDev_PHPUnit_Test_Case
{

    /**
     * @test
     * @return Aoe_Scheduler_Helper_Data
     */
    public function matchesIncludeExclude()
    {
        $helper = Mage::helper('aoe_scheduler');
        /* @var $helper Aoe_Scheduler_Helper_Data */
        $this->assertInstanceOf('Aoe_Scheduler_Helper_Data', $helper);
        return $helper;
    }

    /**
     * @test
     * @dataProvider groupsDataProvider
     */
    public function checkInclude($jobGroups, $include, $exclude, $result)
    {

        $helper = Mage::helper('aoe_scheduler');
        /* @var $helper Aoe_Scheduler_Helper_Data */

        $job = Mage::getModel('aoe_scheduler/job_db');
        /* @var $job Aoe_Scheduler_Model_Job_Db */
        $job->setJobCode('test_job');
        $job->setIsActive(true);
        $job->setGroups($jobGroups);
        $job->save();

        $jobFactory = Mage::getModel('aoe_scheduler/job_factory'); /* @var $jobFactory Aoe_Scheduler_Model_Job_Factory */
        $job = $jobFactory->loadByCode('test_job'); /* @var $job Aoe_Scheduler_Model_Job_Abstract */
        $this->assertEquals($result, $helper->matchesIncludeExclude('test_job', $include, $exclude));
    }

    public function groupsDataProvider()
    {
        return array(
            array('groupA,groupB,groupC', array(), array(), true),
            array('groupA,groupB,groupC', array('groupA'), array(), true),
            array('groupA,groupB,groupC', array('groupB'), array(), true),
            array('groupA,groupB,groupC', array('groupC'), array(), true),
            array('groupA,groupB,groupC', array('groupD'), array(), false),
            array('groupA,groupB,groupC', array('groupA', 'groupB'), array(), true),
            array('groupA,groupB,groupC', array('groupA', 'groupD'), array(), true),
            array('groupA,groupB,groupC', array('groupD', 'groupE'), array(), false),
            array('groupA,groupB,groupC', array(), array('groupD'), true),
            array('groupA,groupB,groupC', array(), array('groupC'), false),
            array('groupA,groupB,groupC', array(), array('groupC', 'groupD'), false),
            array('groupA,groupB,groupC', array('groupB'), array('groupC'), false),
            array('groupA,groupB,groupC', array('groupB'), array('groupB'), false),
        );
    }
}

