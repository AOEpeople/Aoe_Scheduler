<?php

/**
 * Scheduler controller
 *
 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
 */
class Aoe_Scheduler_Adminhtml_TimelineController extends Mage_Adminhtml_Controller_Action {

	/**
	 * Index action (display timeline)
	 *
	 * @return void
	 */
	public function indexAction() {
		$this->loadLayout();
		$block = $this->getLayout()->createBlock('aoe_scheduler/adminhtml_timeline');
		$this->_addContent($block);
		$this->renderLayout();
	}

}
