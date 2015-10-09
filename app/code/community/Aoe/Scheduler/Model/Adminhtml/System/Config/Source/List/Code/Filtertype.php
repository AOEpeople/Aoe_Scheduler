<?php

/**
 * Used in creating options for list code filter type config value selection
 *
 */
class Aoe_Scheduler_Model_Adminhtml_System_Config_Source_List_Code_Filtertype
{
    const SELECT = 'select';
    const TEXT   = 'text';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => self::SELECT, 'label' => Mage::helper('adminhtml')->__('Select')),
            array('value' => self::TEXT, 'label' => Mage::helper('adminhtml')->__('Text')),
        );
    }
}
