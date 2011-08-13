<?php

/**
 * Abstract controller
 *
 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
 */
abstract class Aoe_Scheduler_Adminhtml_AbstractController extends Mage_Adminhtml_Controller_Action {

	/**
	 * Index action (display grid)
	 *
	 * @return void
	 */
	public function indexAction() {
		$this->loadLayout();
		$this->_setActiveMenu('system');
		$this->renderLayout();
	}



	/**
	 * Generate schedule now
	 *
	 * @return void
	 */
	public function generateScheduleAction() {
		$observer = Mage::getModel('cron/observer'); /* @var $observer Mage_Cron_Model_Observer */
		$observer->generate();
		Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Generated schedule'));
		$this->_redirect('*/*/index');
	}

}

