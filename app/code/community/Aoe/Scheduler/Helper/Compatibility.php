<?php

/**
 * Helper
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Helper_Compatibility extends Mage_Core_Helper_Abstract
{

    public function getLocalCodeDir()
    {
        return Mage::getBaseDir('code') . DS . 'local' . DS . 'Aoe' . DS . 'Scheduler';
    }

    public function oldConfigXmlExists()
    {
        return is_file($this->getLocalCodeDir()  . DS . 'etc' . DS . 'config.xml');
    }
}
