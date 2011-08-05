<?php
class Aoe_Scheduler_Block_Adminhtml_Timeline extends Mage_Adminhtml_Block_Template {

	/**
	 * Constructor for Timeline Adminhtml Block
	 */
	public function __construct() {
		$this->_blockGroup = 'aoe_scheduler';
		$this->_controller = 'adminhtml_timeline';
		$this->_headerText = Mage::helper('aoe_scheduler')->__('Timeline');

		parent::__construct();
	}

	/**
	 * Prepare layout
	 *
	 * @return Aoe_Scheduler_Block_Adminhtml_Timeline
	 */
	protected function _prepareLayout() {
		return parent::_prepareLayout();
	}

	/**
	 * Returns the CSS class for the header
	 *
	 * Usually 'icon-head' and a more precise class is returned. We return
	 * only an empty string to avoid spacing on the left of the header as we
	 * don't have an icon.
	 *
	 * @return string
	 */
	public function getHeaderCssClass() {
		return '';
	}

}
