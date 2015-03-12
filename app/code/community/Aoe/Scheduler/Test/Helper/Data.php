<?php

class Aoe_Scheduler_Test_Helper_Data extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @test
     * @return Aoe_Scheduler_Helper_Data
     */
    public function checkClass()
    {
        /* @var Aoe_Scheduler_Helper_Data $helper */
        $helper = Mage::helper('aoe_scheduler');

        $this->assertInstanceOf('Aoe_Scheduler_Helper_Data', $helper);

        return $helper;
    }
}
