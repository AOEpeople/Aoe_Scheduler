<?php
class Aoe_Scheduler_Block_Adminhtml_Cron_Edit_Tab_Settings extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Prepares the form that is to be filled out on the edit.
     *
     * @return Aoe_Scheduler_Block_Adminhtml_Cron_Edit_Settings
     */
    protected function _prepareForm()
    {

        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset('cron_form', array(
             'legend' =>Mage::helper('aoe_scheduler')->__('Settings')
        ));

        $fieldset->addField('model', 'select', array(
             'label'     => Mage::helper('aoe_scheduler')->__('Model'),
             'title'     => Mage::helper('aoe_scheduler')->__('Model'),
             'class' 	=> 'input-select',
             'required'  => true,
             'name'      => 'model',
             'options'    => Mage::helper('aoe_scheduler')->getModelOptions(),
        ));
        $continueButton = $this->getLayout()
            ->createBlock('adminhtml/widget_button')
            ->setData(array(
                'label'     => Mage::helper('aoe_scheduler')->__('Continue'),
                'onclick'   => "setSettings('".$this->getContinueUrl()."', 'model')",
                'class'     => 'save'
            ));
        $fieldset->addField('continue_button', 'note', array(
            'text' => $continueButton->toHtml(),
        ));
        
        return parent::_prepareForm();
    }
    
    /**
     * Return url for continue button
     *
     * @return string
     */
    public function getContinueUrl()
    {
        return $this->getUrl('*/*/*', array(
            '_current' => true,
            'model' => '{{model}}'
        ));
    }    
}