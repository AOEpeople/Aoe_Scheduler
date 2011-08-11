<?php

class Aoe_Scheduler_Block_Adminhtml_Cron_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
	/**
	 * Constructor
	 *
	 * @return nothing
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setId('scheduler_cron_tabs');
		$this->setDestElementId('edit_form');
		$this->setTitle(Mage::helper('aoe_scheduler')->__('Task Setup'));
	}

	/**
	 * Add the general tab if a model has been selected else add the settings tab.
	 *
	 * @return Aoe_Scheduler_Block_Adminhtml_Cron_Edit_Tabs
	 */
	protected function _beforeToHtml() {

		if (Mage::registry('config')->isCompleteToCreate()) {
			$this->addTab('general', array(
                    'label'   => Mage::helper('aoe_scheduler')->__('General'),
                    'title'   => Mage::helper('aoe_scheduler')->__('General'),
                    'content' => $this->getLayout()->createBlock('aoe_scheduler/adminhtml_cron_edit_tab_general')->toHtml(),
			));

		} else {
			$this->addTab('settings', array(
                    'label'   => Mage::helper('aoe_scheduler')->__('Settings'),
                    'title'   => Mage::helper('aoe_scheduler')->__('Settings'),
                    'content' => $this->getLayout()->createBlock('aoe_scheduler/adminhtml_cron_edit_tab_settings')->toHtml(),
			));
		}

		Cds_Log::traceExit();
		return parent::_beforeToHtml();
	}
}