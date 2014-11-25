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
     * @return Aoe_Scheduler_Model_Job_Db
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

        $xmlJob = $job->getXmlJob();
        if ($xmlJob && !$xmlJob->getJobCode()) {
            $xmlJob = false;
        }

        if ($job->getJobCode()) {
            $fieldset->addField('job_code_display', 'text', array(
                'name'  => 'job_code',
                'label' => Mage::helper('aoe_scheduler')->__('Job code'),
                'title' => Mage::helper('aoe_scheduler')->__('Job code'),
                'class' => '',
                'value' => $job->getJobCode(),
                'required' => true,
                'disabled' => true,
            ));
            $fieldset->addField('job_code', 'hidden', array(
                'name'  => 'job_code',
            ));
        } else {
            $fieldset->addField('job_code', 'text', array(
                'name'  => 'job_code',
                'label' => Mage::helper('aoe_scheduler')->__('Job code'),
                'title' => Mage::helper('aoe_scheduler')->__('Job code'),
                'class' => '',
                'required' => true,
                // 'readonly' => $job->getJobCode() ? true : false,
                'disabled' => $job->getJobCode() ? true : false,
                'after_element_html' => $xmlJob ? $this->getOriginalValueSnippet($xmlJob->getJobCode()) : ''
            ));
        }

        $fieldset->addField('name', 'text', array(
            'name'  => 'name',
            'label' => Mage::helper('aoe_scheduler')->__('Name'),
            'title' => Mage::helper('aoe_scheduler')->__('Name'),
            'class' => '',
            'required' => false,
            'after_element_html' => $xmlJob ? $this->getOriginalValueSnippet($xmlJob->getName()) : ''
        ));

        $fieldset->addField('short_description', 'textarea', array(
            'name'  => 'short_description',
            'label' => Mage::helper('aoe_scheduler')->__('Short description'),
            'title' => Mage::helper('aoe_scheduler')->__('Short description'),
            'class' => '',
            'required' => false,
            'after_element_html' => $xmlJob ? $this->getOriginalValueSnippet($xmlJob->getShortDescription()) : ''
        ));

        $fieldset->addField('description', 'textarea', array(
            'name'  => 'description',
            'label' => Mage::helper('aoe_scheduler')->__('Description'),
            'title' => Mage::helper('aoe_scheduler')->__('Description'),
            'class' => '',
            'required' => false,
            'after_element_html' => $xmlJob ? $this->getOriginalValueSnippet($xmlJob->getDescription()) : ''
        ));

        $fieldset->addField('run_model', 'text', array(
            'name'  => 'run_model',
            'label' => Mage::helper('aoe_scheduler')->__('Run model'),
            'title' => Mage::helper('aoe_scheduler')->__('Run model'),
            'class' => '',
            'required' => true,
            'note' => Mage::helper('aoe_scheduler')->__('e.g. "aoe_scheduler/task_heartbeat::run"'),
            'after_element_html' => $xmlJob ? $this->getOriginalValueSnippet($xmlJob->getRunModel()) : ''
        ));

        $fieldset->addField('is_active', 'select', array(
            'name'      => 'is_active',
            'label'     => Mage::helper('aoe_scheduler')->__('Status'),
            'title'     => Mage::helper('aoe_scheduler')->__('Status'),
            'required'  => true,
            'options'   => array(
                0 => Mage::helper('aoe_scheduler')->__('Disabled'),
                1 => Mage::helper('aoe_scheduler')->__('Enabled')
            ),
            'after_element_html' => $xmlJob ? $this->getOriginalValueSnippet($xmlJob->getIsActive() ? Mage::helper('aoe_scheduler')->__('Enabled') : Mage::helper('aoe_scheduler')->__('Disabled')) : ''
        ));

        $fieldset = $form->addFieldset('cron_fieldset', array('legend' => Mage::helper('aoe_scheduler')->__('Scheduling')));
        $this->_addElementTypes($fieldset);

        $fieldset->addField('schedule_config_path', 'text', array(
            'name'  => 'schedule_config_path',
            'label' => Mage::helper('aoe_scheduler')->__('Cron configuration path'),
            'title' => Mage::helper('aoe_scheduler')->__('Cron configuration path'),
            'class' => '',
            'required' => false,
            'note' => Mage::helper('aoe_scheduler')->__('Path to system configuration containing the cron configuration for this job. (e.g. system/cron/scheduler_cron_expr_heartbeat) This configuration - if set - has a higher priority over the cron expression configured with the job directly.'),
            'after_element_html' => $xmlJob ? $this->getOriginalValueSnippet($xmlJob->getScheduleConfigPath()) : ''
        ));

        $fieldset->addField('schedule_cron_expr', 'text', array(
            'name'      => 'schedule_cron_expr',
            'label'     => Mage::helper('aoe_scheduler')->__('Cron expression'),
            'title'     => Mage::helper('aoe_scheduler')->__('Cron expression'),
            'required'  => false,
            'note' => Mage::helper('aoe_scheduler')->__('e.g "*/5 * * * *" or "always"'),
            'after_element_html' => $xmlJob ? $this->getOriginalValueSnippet($xmlJob->getScheduleCronExpr()) : ''
        ));

        $fieldset = $form->addFieldset('parameter_fieldset', array('legend' => Mage::helper('aoe_scheduler')->__('Extras')));
        $this->_addElementTypes($fieldset);

        $fieldset->addField('parameter', 'textarea', array(
            'name'  => 'parameter',
            'label' => Mage::helper('aoe_scheduler')->__('Parameters'),
            'title' => Mage::helper('aoe_scheduler')->__('Parameters'),
            'class' => 'textarea',
            'required' => false,
            'note' => Mage::helper('aoe_scheduler')->__('This parameter will be passed to the model. It is up to the model to specify the format of this parameter (e.g. json/xml/...'),
            'after_element_html' => $xmlJob ? $this->getOriginalValueSnippet($xmlJob->getParameter()) : ''
        ));

        $fieldset->addField('groups', 'textarea', array(
            'name'  => 'groups',
            'label' => Mage::helper('aoe_scheduler')->__('Groups'),
            'title' => Mage::helper('aoe_scheduler')->__('Groups'),
            'class' => 'textarea',
            'required' => false,
            'note' => Mage::helper('aoe_scheduler')->__('Comma-separated list of groups (tags) that can be used with the include/exclude command line options of scheduler.php'),
            'after_element_html' => $xmlJob ? $this->getOriginalValueSnippet($xmlJob->getGroups()) : ''
        ));

        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function getOriginalValueSnippet($value)
    {
        if (empty($value)) {
            $value = '<em>empty</em>';
        }
        return '<p class="original" style="background-color: white"><strong>Original:</strong> '.$value.'</p>';
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
