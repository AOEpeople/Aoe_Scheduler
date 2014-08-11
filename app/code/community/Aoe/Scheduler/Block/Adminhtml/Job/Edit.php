<?php
/**
 * Job edit container
 *
 * @author Fabrizio Branca
 * @since 2014-08-09
 */
class Aoe_Scheduler_Block_Adminhtml_Job_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{

    /**
     * Internal constructor
     *
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_objectId = 'job_id';
        $this->_blockGroup = 'aoe_scheduler';
        $this->_controller = 'adminhtml_cron';

        $this->_addButton('save_and_edit_button', array(
                'label'   => Mage::helper('aoe_scheduler')->__('Save and Continue Edit'),
                'onclick' => 'saveAndContinueEdit()',
                'class'   => 'save'
            ), 100
        );

        $this->_formScripts[] = '
            function saveAndContinueEdit() {
            editForm.submit($(\'edit_form\').action + \'back/edit/\');}';
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
     * Return translated header text depending on creating/editing action
     *
     * @return string
     */
    public function getHeaderText()
    {
        if ($this->getJob()->getId()) {
            return Mage::helper('aoe_scheduler')->__('Job [id: %s]', $this->escapeHtml($this->getJob()->getId()));
        }
        else {
            return Mage::helper('aoe_scheduler')->__('New Job');
        }
    }

    /**
     * Return save url for edit form
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('*/*/save', array('_current'=>true, 'back'=>null));
    }
}