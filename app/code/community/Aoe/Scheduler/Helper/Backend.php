<?php

class Aoe_Scheduler_Helper_Backend extends Mage_Core_Helper_Abstract
{
    const XML_PATH_SHOW_NAME_COLUMN = 'system/cron/showNameColumn';
    const XML_PATH_SHOW_SHORT_DESC_COLUMN = 'system/cron/showShortDescColumn';

    /**
     * @return bool
     */
    public function showNameColumn()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_SHOW_NAME_COLUMN);
    }

    /**
     * @return bool
     */
    public function showShortDescColumn()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_SHOW_SHORT_DESC_COLUMN);
    }
}
