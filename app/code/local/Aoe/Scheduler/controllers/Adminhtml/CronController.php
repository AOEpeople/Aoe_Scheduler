<?php

require_once Mage::getModuleDir('controllers', 'Aoe_Scheduler').'/Adminhtml/AbstractController.php';

/**
 * Cron controller
 *
 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
 */
class Aoe_Scheduler_Adminhtml_CronController extends Aoe_Scheduler_Adminhtml_AbstractController {



	/**
	 * Mass action: disable
	 *
	 * @return void
	 */
	public function disableAction() {
		$codes = $this->getRequest()->getParam('codes');
		$disabledCrons = Mage::helper('aoe_scheduler')->trimExplode(',', Mage::getStoreConfig('system/cron/disabled_crons'), true);
		foreach ($codes as $code) {
			if (!in_array($code, $disabledCrons)) {
				$disabledCrons[] = $code;
				Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Disabled "%s"', $code));
			}
		}
		Mage::getModel('core/config')->saveConfig('system/cron/disabled_crons/', implode(',', $disabledCrons));
		Mage::app()->getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array(Mage_Core_Model_Config::CACHE_TAG));
		$this->_redirect('*/*/index');
	}



	/**
	 * Mass action: enable
	 *
	 * @return void
	 */
	public function enableAction() {
		$codes = $this->getRequest()->getParam('codes');
		$disabledCrons = Mage::helper('aoe_scheduler')->trimExplode(',', Mage::getStoreConfig('system/cron/disabled_crons'), true);
		foreach ($codes as $key => $code) {
			if (in_array($code, $disabledCrons)) {
				unset($disabledCrons[array_search($code, $disabledCrons)]);
				Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Enabled "%s"', $code));
			}
		}
		Mage::getModel('core/config')->saveConfig('system/cron/disabled_crons/', implode(',', $disabledCrons));
		Mage::app()->getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array(Mage_Core_Model_Config::CACHE_TAG));
		$this->_redirect('*/*/index');
	}




	/**
	 * Mass action: schedule now
	 *
	 * @return void
	 */
	public function scheduleNowAction() {
		$codes = $this->getRequest()->getParam('codes');
		foreach ($codes as $key) {
			Mage::getModel('cron/schedule') /* @var Aoe_Scheduler_Model_Schedule */
				->setJobCode($key)
				->scheduleNow()
				->save();
			Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Scheduled "%s"', $key));
		}
		$this->_redirect('*/*/index');
	}



	/**
	 * Mass action: run now
	 *
	 * @return void
	 */
	public function runNowAction() {
		$codes = $this->getRequest()->getParam('codes');
		foreach ($codes as $key) {
			$schedule = Mage::getModel('cron/schedule') /* @var $schedule Aoe_Scheduler_Model_Schedule */
				->setJobCode($key)
				->runNow()
				->save();
			Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Ran "%s" (Duration: %s sec)', $key, intval($schedule->getDuration())));
		}
		$this->_redirect('*/*/index');
	}

}

