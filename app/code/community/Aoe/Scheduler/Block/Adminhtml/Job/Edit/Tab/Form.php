<?php

/**
 * Job form tab block
 *
 * @author Fabrizio Branca
 * @since  2014-08-09
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
        return $this->__('General');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return $this->__('General');
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
     * @return bool
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
        $form = new Varien_Data_Form(
            ['id'     => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']
        );

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => $this->__('General')]);
        $this->_addElementTypes($fieldset);

        $fieldset->addField(
            'job_code',
            'text',
            ['name'     => 'job_code', 'label'    => $this->__('Job code'), 'title'    => $this->__('Job code'), 'class'    => '', 'required' => true, 'disabled' => $job->getJobCode() ? true : false]
        );

        $fieldset->addField(
            'name',
            'text',
            ['name'               => 'name', 'label'              => $this->__('Name'), 'title'              => $this->__('Name'), 'class'              => '', 'required'           => false, 'after_element_html' => $this->getOriginalValueSnippet($job, 'name')]
        );

        $fieldset->addField(
            'short_description',
            'textarea',
            ['name'               => 'short_description', 'label'              => $this->__('Short description'), 'title'              => $this->__('Short description'), 'class'              => '', 'required'           => false, 'after_element_html' => $this->getOriginalValueSnippet($job, 'short_description')]
        );

        $fieldset->addField(
            'description',
            'textarea',
            ['name'               => 'description', 'label'              => $this->__('Description'), 'title'              => $this->__('Description'), 'class'              => '', 'required'           => false, 'after_element_html' => $this->getOriginalValueSnippet($job, 'description')]
        );

        $fieldset->addField(
            'run_model',
            'text',
            ['name'               => 'run_model', 'label'              => $this->__('Run model'), 'title'              => $this->__('Run model'), 'class'              => '', 'required'           => true, 'note'               => $this->__('e.g. "aoe_scheduler/task_heartbeat::run"'), 'after_element_html' => $this->getOriginalValueSnippet($job, 'run/model')]
        );

        $fieldset->addField(
            'is_active',
            'select',
            ['name'               => 'is_active', 'label'              => $this->__('Status'), 'title'              => $this->__('Status'), 'required'           => true, 'options'            => [0 => $this->__('Disabled'), 1 => $this->__('Enabled')], 'after_element_html' => $this->getOriginalValueSnippetFlag($job, 'is_active', 'Enabled', 'Disabled')]
        );

        $fieldset = $form->addFieldset('cron_fieldset', ['legend' => $this->__('Scheduling')]);
        $this->_addElementTypes($fieldset);

        $fieldset->addField(
            'schedule_config_path',
            'text',
            ['name'               => 'schedule_config_path', 'label'              => $this->__('Cron configuration path'), 'title'              => $this->__('Cron configuration path'), 'class'              => '', 'required'           => false, 'note'               => $this->__(
                'Path to system configuration containing the cron configuration for this job. (e.g. system/cron/scheduler_cron_expr_heartbeat) This configuration - if set - has a higher priority over the cron expression configured with the job directly.'
            ), 'after_element_html' => $this->getOriginalValueSnippet($job, 'schedule/config_path')]
        );

        $fieldset->addField(
            'schedule_cron_expr',
            'text',
            ['name'               => 'schedule_cron_expr', 'label'              => $this->__('Cron expression'), 'title'              => $this->__('Cron expression'), 'required'           => false, 'note'               => $this->__('e.g "*/5 * * * *" or "always"'), 'after_element_html' => $this->getOriginalValueSnippet($job, 'schedule/cron_expr')]
        );

        $fieldset = $form->addFieldset('parameter_fieldset', ['legend' => $this->__('Extras')]);
        $this->_addElementTypes($fieldset);

        $fieldset->addField(
            'parameters',
            'textarea',
            ['name'               => 'parameters', 'label'              => $this->__('Parameters'), 'title'              => $this->__('Parameters'), 'class'              => 'textarea', 'required'           => false, 'note'               => $this->__('These parameters will be passed to the model. It is up to the model to specify the format of these parameters (e.g. json/xml/...'), 'after_element_html' => $this->getOriginalValueSnippet($job, 'parameters')]
        );

        $fieldset->addField(
            'groups',
            'textarea',
            ['name'               => 'groups', 'label'              => $this->__('Groups'), 'title'              => $this->__('Groups'), 'class'              => 'textarea', 'required'           => false, 'note'               => $this->__('Comma-separated list of groups (tags) that can be used with the include/exclude command line options of scheduler.php'), 'after_element_html' => $this->getOriginalValueSnippet($job, 'groups')]
        );

        $fieldset = $form->addFieldset('dependency_fieldset', ['legend' => $this->__('Dependencies')]);
        $this->_addElementTypes($fieldset);

        $fieldset->addField(
            'on_success',
            'textarea',
            ['name'               => 'on_success', 'label'              => $this->__('Run jobs on success'), 'title'              => $this->__('Run jobs on success'), 'class'              => 'textarea', 'required'           => false, 'note'               => $this->__('Comma-separated list of job codes that will be scheduled after the current cron job has completed successfully.')]
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function getOriginalValueSnippet(Aoe_Scheduler_Model_Job $job, $key)
    {
        if ($job->isDbOnly()) {
            return '';
        }

        $xmlJobData = $job->getXmlJobData();
        if (!array_key_exists($key, $xmlJobData)) {
            return '';
        }

        $value = $xmlJobData[$key];
        if ($value === null || $value === '') {
            $value = '<em>empty</em>';
        }

        return '<p class="original" style="background-color: white"><strong>Original:</strong> ' . $value . '</p>';
    }

    protected function getOriginalValueSnippetFlag(Aoe_Scheduler_Model_Job $job, $key, $trueLabel, $falseLabel)
    {
        if ($job->isDbOnly()) {
            return '';
        }

        $xmlJobData = $job->getXmlJobData();
        if (!array_key_exists($key, $xmlJobData)) {
            return '';
        }

        $value = $this->__(!in_array($xmlJobData[$key], [false, 'false', 0, '0'], true) ? $trueLabel : $falseLabel);

        return '<p class="original" style="background-color: white"><strong>Original:</strong> ' . $value . '</p>';
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
