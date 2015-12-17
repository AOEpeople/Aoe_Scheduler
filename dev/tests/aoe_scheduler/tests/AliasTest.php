<?php

class AliasTest extends AbstractTest
{

    /**
     * @test
     */
    public function helperAlias()
    {
        $helper = Mage::helper('aoe_scheduler');
        $this->assertInstanceOf('Aoe_Scheduler_Helper_Data', $helper);
    }

    /**
     * @test
     */
    public function testShouldReturnScheduleModelFromAlias()
    {
        $model = Mage::getModel('cron/schedule');
        $this->assertInstanceOf('Aoe_Scheduler_Model_Schedule', $model);
    }
}
