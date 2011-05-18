<?php

/**
 * Scheduler controller
 *
 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
 */
class Aoe_Scheduler_Adminhtml_SchedulerController extends Mage_Adminhtml_Controller_Action {

	/**
	 * Index action: Display grid
	 *
	 * @return void
	 */
	public function indexAction() {
		$this->loadLayout();
		$block = $this->getLayout()->createBlock('aoe_scheduler/adminhtml_scheduler');
		$this->_addContent($block);
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



	/**
	 * Mass action: delete
	 *
	 * @return void
	 */
	public function deleteAction() {
		$ids = $this->getRequest()->getParam('schedule_ids');
		foreach ($ids as $id) {
			$schedule = Mage::getModel('cron/schedule')->load($id)->delete(); /* @var $schedule Mage_Cron_Model_Schedule */
			Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Deleted task "%s"', $id));
		}
		$this->_redirect('*/*/index');
	}

}

