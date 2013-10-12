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
			$schedule = Mage::getModel('cron/schedule')->load($id)->delete(); /* @var $schedule Aoe_Scheduler_Model_Schedule */
		}
		Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Deleted task(s) "%s"', implode(', ', $ids)));
		$this->_redirect('*/*/index');
	}

    /**
     * Mass action: kill
     *
     * @return void
     */
    public function killAction() {
        $ids = $this->getRequest()->getParam('schedule_ids');
        foreach ($ids as $id) {
            $schedule = Mage::getModel('cron/schedule')/* @var $schedule Aoe_Scheduler_Model_Schedule */
                ->load($id)
                ->setKillRequest(strftime('%Y-%m-%d %H:%M:%S', time()))
                ->save();
        }
        Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Kill requests saved for task(s) "%s" (will be killed via cron)', implode(', ', $ids)));
        $this->_redirect('*/*/index');
    }

}
