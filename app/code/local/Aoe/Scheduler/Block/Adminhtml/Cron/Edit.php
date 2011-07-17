<?php
class Aoe_Scheduler_Block_Adminhtml_Cron_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{

    /**
     * Constructor
     *
     * @return nothing
     */
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'aoe_scheduler';
        $this->_controller = 'adminhtml_cron';
        $this->_mode = 'edit';
        if (Mage::registry('config')) {
            
            $this->_addButton('save_and_continue', array(
                  'label' => Mage::helper('aoe_scheduler')->__('Save And Continue Edit'),
                  'onclick' => 'saveAndContinueEdit()',
                  'class' => 'save',
            ), -100);
            $this->_formScripts[] = " function saveAndContinueEdit(){ editForm.submit($('edit_form').action + 'back/edit/') } ";
            $this->_updateButton('save', 'label', Mage::helper('aoe_scheduler')->__('Save Task'));
            
        } else {
            $this->removeButton('save');            
        }

        $this->removeButton('delete');
    }

    /**
     * Gets the header text for the new and edit pages.
     *
     * @return string
     */
    public function getHeaderText()
    {
        if (Mage::registry('config')) {
            return Mage::helper('aoe_scheduler')->__('Edit Task');
        } else {
            return Mage::helper('aoe_scheduler')->__('New Task');
        }
    }
    
}