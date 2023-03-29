<?php

/**
 * Used in creating options for list code filter type config value selection
 *
 */
class Aoe_Scheduler_Model_Adminhtml_System_Config_Source_List_Code_Filtertype
{
    public const SELECT = 'select';
    public const TEXT   = 'text';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => self::SELECT, 'label' => Mage::helper('adminhtml')->__('Select')], ['value' => self::TEXT, 'label' => Mage::helper('adminhtml')->__('Text')]];
    }
}
