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
        $fieldset = $form->addFieldset('cron_form', array(
             'legend' =>Mage::helper('aoe_scheduler')->__('Task Information')
        ));
        
        $fieldset->addField('job_code', 'text', array(
             'label'     => Mage::helper('aoe_scheduler')->__('Code'),
             'class'     => 'required-entry',
             'required'  => true,
        	 'note'   => Mage::helper('aoe_scheduler')->__('Note: If you use an existing code it will overwrite the existing task.'),
             'name'      => 'job_code'
        ));
        
        $fieldset->addField('cron_expr', 'text', array(
             'label'     => Mage::helper('aoe_scheduler')->__('Cron Expression'),
             'class'     => 'required-entry',
             'required'  => true,        
             'name'      => 'cron_expr'
        ));
        
        $fieldset->addField('model', 'select', array(
             'label'     => Mage::helper('aoe_scheduler')->__('Model'),
        	 'title'	 => Mage::helper('aoe_scheduler')->__('Model'),
             'class'     => 'required-entry',
             'required'  => true,
             'name'      => 'model',
        	 'options'	 => $this->_getModelOptions()
        ));
        
		$config = Mage::registry('config');
		$data = array();
		
		if ($config) {
	        //if this is an update then disable job_code?
	        $data['job_code'] = $this->getRequest()->getParam('id');
	        $data['cron_expr'] = $config->getCronExpr();
	        $data['model'] = $config->getModel();
		}
			
	    $form->setValues($data);
		return parent::_prepareForm();
    }
    
    protected function _getModelOptions() {
    	$models = array();
		$collection = Mage::getModel('aoe_scheduler/collection_crons');
		foreach ($collection as $item) {
			$models[$item->getModel()] = $item->getModel();
		}
		return $models;
    }
}
