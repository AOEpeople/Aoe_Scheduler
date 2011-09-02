<?php
class Aoe_Scheduler_Block_Adminhtml_Cron_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
	/**
     * Prepares the form that is to be filled out on the edit.
     *
     * @return Aoe_Scheduler_Block_Adminhtml_Crond_Edit_Form
     */
	protected function _prepareForm()
    {
    	
        $form = new Varien_Data_Form(array(
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'),'action' => 'edit')),
                'method' => 'post',
                'enctype' => 'multipart/form-data',
        ));
        $form->setUseContainer(true);
        $this->setForm($form);
		return parent::_prepareForm();
    }
    
}
