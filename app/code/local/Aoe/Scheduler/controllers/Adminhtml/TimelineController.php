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
		$this->_setActiveMenu('system');
		$this->renderLayout();
	}

}
