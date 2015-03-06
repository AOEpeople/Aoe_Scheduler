<?php

/**
 * Job block
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Block_Adminhtml_Instructions extends Mage_Adminhtml_Block_Template
{

    public function getCurrentUser()
    {
        return trim(shell_exec('whoami'));
    }

    public function getMagentoRootpath()
    {
        return Mage::getBaseDir();
    }
}
