<?php
/**
 * Job form tab block
 *
 * @author Fabrizio Branca
 * @since 2014-08-09
 */
class Aoe_Scheduler_Block_Adminhtml_Job_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * Internal constructor
     *
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setActive(true);
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return Mage::helper('aoe_scheduler')->__('General');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('aoe_scheduler')->__('General');
    }

    /**
     * Returns status flag about this tab can be shown or not
     *
     * @return true
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return true
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Get job
     *
     * @return Aoe_Scheduler_Model_Job
     */
    public function getJob()
    {
        return Mage::registry('current_job_instance');
    }


    /**
     * Prepare form before rendering HTML
     *
     * @return Mage_Widget_Block_Adminhtml_Widget_Instance_Edit_Tab_Main
     */
    protected function _prepareForm()
    {
        $job = $this->getJob();
        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getData('action'),
            'method' => 'post'
        ));

        $fieldset = $form->addFieldset('base_fieldset', array('legend' => Mage::helper('aoe_scheduler')->__('General')));
        $this->_addElementTypes($fieldset);

        if ($job->getId()) {
            $fieldset->addField('job_id', 'hidden', array(
                'name' => 'job_id',
            ));
        }

        $fieldset->addField('job_code', 'text', array(
            'name'  => 'Job Code',
            'label' => Mage::helper('aoe_scheduler')->__('Job code'),
            'title' => Mage::helper('aoe_scheduler')->__('Job code'),
            'class' => '',
            'required' => true,
        ));

        $fieldset->addField('run_model', 'text', array(
            'name'  => 'run_model',
            'label' => Mage::helper('aoe_scheduler')->__('Run model'),
            'title' => Mage::helper('aoe_scheduler')->__('Run model'),
            'class' => '',
            'required' => true,
            'note' => Mage::helper('aoe_scheduler')->__('e.g. "aoe_scheduler/heartbeatTask::run"')
        ));

        $fieldset->addField('is_active', 'select', array(
            'label'     => Mage::helper('aoe_scheduler')->__('Status'),
            'title'     => Mage::helper('aoe_scheduler')->__('Status'),
            'name'      => 'is_active',
            'required'  => true,
            'options'   => array(
                0 => Mage::helper('aoe_scheduler')->__('Disabled'),
                1 => Mage::helper('aoe_scheduler')->__('Enabled')
            )
        ));

        $fieldset = $form->addFieldset('cron_fieldset', array('legend' => Mage::helper('aoe_scheduler')->__('Scheduling')));
        $this->_addElementTypes($fieldset);

        $fieldset->addField('schedule_config_path', 'text', array(
            'name'  => 'schedule_config_path',
            'label' => Mage::helper('aoe_scheduler')->__('Cron configuration path'),
            'title' => Mage::helper('aoe_scheduler')->__('Cron configuration path'),
            'class' => '',
            'required' => false,
            'note' => Mage::helper('aoe_scheduler')->__('Path to system configuration containing the cron configuration for this job. (e.g. system/cron/scheduler_cron_expr_heartbeat) This configuration - if set - has a higher priority over the cron expression configured with the job directly.')
        ));

        $fieldset->addField('schedule_cron_expr', 'text', array(
            'name'      => 'area',
            'label'     => Mage::helper('aoe_scheduler')->__('Cron expression'),
            'title'     => Mage::helper('aoe_scheduler')->__('Cron expression'),
            'required'  => false,
            'note' => Mage::helper('aoe_scheduler')->__('e.g "*/5 * * * *" or "always"')
        ));

        $fieldset = $form->addFieldset('parameter_fieldset', array('legend' => Mage::helper('aoe_scheduler')->__('Extras')));
        $this->_addElementTypes($fieldset);

        $fieldset->addField('parameter', 'textarea', array(
            'name'  => 'parameter',
            'label' => Mage::helper('aoe_scheduler')->__('Parameters'),
            'title' => Mage::helper('aoe_scheduler')->__('Parameters'),
            'class' => 'textarea',
            'required' => false,
            'note' => Mage::helper('aoe_scheduler')->__('This parameter will be passed to the model. It is up to the model to specify the format of this paramter (e.g. json/xml/...')
        ));

        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Initialize form fields values
     *
     * @return $this
     */
    protected function _initFormValues()
    {
        $this->getForm()->addValues($this->getJob()->getData());
        return parent::_initFormValues();
    }
}