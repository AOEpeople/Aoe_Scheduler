<?php

class Aoe_Scheduler_Block_Adminhtml_Scheduler_Renderer_Memory
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value = $row->getMemoryUsage();
        if ($value) {
            return number_format($row->getMemoryUsage(), 2) . ' MB';
        }
        return parent::render($row);
    }
}
