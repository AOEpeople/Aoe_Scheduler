<?php

class Aoe_Scheduler_Test_Helper_Data extends EcomDev_PHPUnit_Test_Case
{
    /**
     * When the user doesn't want the incorrect user running cron message disabled,
     * the method should return true
     *
     * @loadFixture userCronMessageDisabled
     */
    public function testShouldReturnTrueWhenUserCronMessageDisabled()
    {
        $result = Mage::helper('aoe_scheduler')->runningAsConfiguredUser();
        $this->assertTrue($result);
    }

    /**
     * When specifying that $useRunningUser is true, getRunningUser() should be called.
     * While we're there, it should return the same as what's in configuration
     *
     * @param string $method         The method that should be called
     * @param string $user           The user that will be returned
     * @param bool   $useRunningUser Whether to use the current running user or the last run
     *
     * @loadFixture configuredUser
     * @loadFixture userCronMessageEnabled
     * @dataProvider dataProvider
     */
    public function testShouldCallCorrectUserMethodAndPerformMatch($method, $user, $useRunningUser)
    {
        $mock = $this->getHelperMock('aoe_scheduler', array('getRunningUser', 'getLastRunUser'));

        $mock->expects($this->once())->method($method)->will($this->returnValue($user));

        $result = $mock->runningAsConfiguredUser($useRunningUser);
        // $useRunningUser is also the expectation
        $this->assertSame($useRunningUser, $result);
    }
}
