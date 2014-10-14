<?php

/**
 * Job Collection
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Model_Job_Collection extends Varien_Data_Collection
{

    /**
     * Convert items array to hash for select options
     *
     * return items hash
     * array($value => $label)
     *
     * @param   string $valueField
     * @param   string $labelField
     * @return  array
     */
    protected function _toOptionHash($valueField = 'id', $labelField = 'name')
    {
        $res = array();
        foreach ($this as $item) { /* @var $item Aoe_Scheduler_Model_Job_Abstract */
            $code = $item->getJobCode();
            $res[$code] = $code;
        }
        return $res;
    }

}