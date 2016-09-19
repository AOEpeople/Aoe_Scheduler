<?php

/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 02/09/16
 * Time: 17:36
 */
class Aoe_Scheduler_Model_Adminhtml_Source_Memorylimit
{

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            '' => Mage::helper('aoe_scheduler')->__('Default'),
            '128M' => '128 Mo',
            '256M' => '256 Mo',
            '512M' => '512 Mo'
        );
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();
        $array = $this->toArray();
        foreach ($array as $value => $label) {
            $options[] = array(
                'label' => $label,
                'value' => $value
            );
        }
        return $options;
    }

}