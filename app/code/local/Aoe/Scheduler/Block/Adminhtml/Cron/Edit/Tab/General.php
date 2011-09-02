<?php
class Aoe_Scheduler_Block_Adminhtml_Cron_Edit_Tab_General extends Mage_Adminhtml_Block_Widget_Form
{
	/**
     * Prepares the form that is to be filled out on the edit.
     *
     * @return Aoe_Scheduler_Block_Adminhtml_Crond_Edit_Form
     */
	protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
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
                
        $fieldset->addField('model', 'hidden', array(
             'name'      => 'model'));
        
		$config = Mage::registry('config');
		$data = array();
		if ($config) {
		
			if ($options = $config->getOptions()) {
				$data = $this->_addOptions($options,$fieldset);
			} else {
				$configs = $config->findByModel($config->getModel());
				foreach ($configs as $configuration) {
					$data = $this->_addOptions($configuration->getOptions(),$fieldset);
				}							
			}
		
	        $data['job_code'] = $this->getRequest()->getParam('id');
	        $data['cron_expr'] = $config->getCronExpr();
	        $data['model'] = $config->getModel();
		}
			
	    $form->setValues($data);
		return parent::_prepareForm();
    }

	/**
     * Add options to the form and populate the return an array of data for the form.
     *
     * @return array
     */    
    private function _addOptions($options,$fieldset) {
    	$data = array();
		foreach ($options as $optionName => $optionValue) {
	        $fieldset->addField($optionName, 'text', array(
	             'label'     => Mage::helper('aoe_scheduler')->__($optionName),
	             'required'  => false,        
	             'name'      => $optionName
	        ));
	        $data[$optionName] = $optionValue;				
		}
		return $data;    	
    }
}
