<?php
/**
 * Contains unit tests related to Magento XML alias resolutions - migrated from other
 * tests
 *
 * @category Mage
 * @package  Aoe_Scheduler
 * @author   Fabrizio Branca
 */
class Aoe_Scheduler_Test_Config_AliasTest extends EcomDev_PHPUnit_Test_Case_Config
{
    /**
     * When calling the Magento helper alias, the class should resolve correctly
     */
    public function testShouldReturnSchedulerHelperFromAlias()
    {
        $this->assertHelperAlias('aoe_scheduler', 'Aoe_Scheduler_Helper_Data');
    }

    /**
     * Check that the schedule model rewrite is configured
     */
    public function testShouldReturnScheduleModelFromAlias()
    {
        $this->assertModelAlias('cron/schedule', 'Aoe_Scheduler_Model_Schedule');
    }
}
