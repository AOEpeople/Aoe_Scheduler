<?php

require_once Mage::getModuleDir('controllers', 'Aoe_Scheduler').'/Adminhtml/AbstractController.php';

/**
 * Scheduler controller
 *
 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
 */
class Aoe_Scheduler_Adminhtml_SchedulerController extends Aoe_Scheduler_Adminhtml_AbstractController {



	/**
	 * Mass action: delete
	 *
	 * @return void
	 */
	public function deleteAction() {
		$ids = $this->getRequest()->getParam('schedule_ids');
		foreach ($ids as $id) {
			$schedule = Mage::getModel('cron/schedule')->load($id)->delete(); /* @var $schedule Mage_Cron_Model_Schedule */
		}
		Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Deleted task(s) "%s"', implode(', ', $ids)));
		$this->_redirect('*/*/index');
	}

}
