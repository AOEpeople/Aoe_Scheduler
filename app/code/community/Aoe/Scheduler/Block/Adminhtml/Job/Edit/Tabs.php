<?php
/**
 * Job edit tabs container
 *
 * @author Fabrizio Branca
 * @since 2014-08-09
 */
class Aoe_Scheduler_Block_Adminhtml_Job_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    /**
     * Internal constructor
     *
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('job_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle($this->__('Job'));
    }
}
