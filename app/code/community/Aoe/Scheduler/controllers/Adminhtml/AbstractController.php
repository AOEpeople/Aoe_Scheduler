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
		$this->checkHeartbeat();

		$this->loadLayout();

		$this->_setActiveMenu('system');
		$this->renderLayout();
	}



	/**
	 * Check heartbeat
	 */
	protected function checkHeartbeat() {
		if (!Mage::helper('aoe_scheduler')->isDisabled('aoescheduler_heartbeat')) {
			$lastHeartbeat = Mage::helper('aoe_scheduler')->getLastHeartbeat();
			if ($lastHeartbeat === false) {
				// no heartbeat task found
				$this->_getSession()->addError('No heartbeat task found. Check if cron is configured correctly.');
			} else {
				$timespan = Mage::helper('aoe_scheduler')->dateDiff($lastHeartbeat);
				if ($timespan <= 5 * 60) {
					$this->_getSession()->addSuccess(sprintf('Scheduler is working. (Last execution: %s minute(s) ago)', round($timespan/60)));
				} elseif ($timespan > 5 * 60 && $timespan <= 60 * 60 ) {
					// heartbeat wasn't executed in the last 5 minutes. Heartbeat schedule could be modified to not run every five minutes!
					$this->_getSession()->addNotice(sprintf('Last heartbeat is older than %s minutes.', round($timespan/60)));
				} else {
					// everything ok
					$this->_getSession()->addError('Last heartbeat is older than one hour. Please check your settings and your configuration!');
				}
			}

		}
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

