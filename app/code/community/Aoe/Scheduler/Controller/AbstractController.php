<?php

/**
 * Abstract controller
 *
 * @author Fabrizio Branca
 */
abstract class Aoe_Scheduler_Controller_AbstractController extends Mage_Adminhtml_Controller_Action
{

    public function preDispatch()
    {
        parent::preDispatch();
        if ($this->getRequest()->getActionName() != 'error' && !$this->checkLocalCodePool()) {
            $this->_forward('error');
        }
    }

    public function errorAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('system');
        $this->renderLayout();
    }

    /**
     * Index action
     *
     * @return void
     */
    public function indexAction()
    {

        if (!Mage::getStoreConfigFlag('system/cron/enable')) {
            $this->_getSession()->addNotice($this->__('Scheduler is disabled in configuration (system/cron/enable). No schedules will be executed.'));
        } else {
            $this->checkHeartbeat();
        }

        // check configuration
        if (Mage::getStoreConfig('system/cron/schedule_generate_every') > Mage::getStoreConfig('system/cron/schedule_ahead_for')) {
            $this->_getSession()->addError($this->__('Configuration problem. "Generate Schedules Every" is higher than "Schedule Ahead for". Please check your <a href="%s">configuration settings</a>.', $this->getUrl('adminhtml/system_config/edit', array('section' => 'system')) . '#system_cron'));
        }

        $this->loadLayout();

        $this->_setActiveMenu('system');
        $this->renderLayout();
    }

    /**
     * Aoe_Scheduler used to live in the local code pool.
     * When newer version are installed without removing the old files Aoe_Scheduler will produce fatal errors.
     * This is an attempt to handle this a little better.
     */
    protected function checkLocalCodePool()
    {
        $helper = Mage::helper('aoe_scheduler/compatibility'); /* @var $helper Aoe_Scheduler_Helper_Compatibility */
        if ($helper->oldConfigXmlExists()) {
            $this->_getSession()->addError($this->__('Looks like you have an older version of Aoe_Scheduler installed that lived in the local code pool. Please delete everything under "%s"', $helper->getLocalCodeDir()));
            return false;
        }
        return true;
    }

    /**
     * Check heartbeat
     */
    protected function checkHeartbeat()
    {
        if (!Mage::helper('aoe_scheduler')->isDisabled('aoescheduler_heartbeat')) {
            $lastHeartbeat = Mage::helper('aoe_scheduler')->getLastHeartbeat();
            if ($lastHeartbeat === false) {
                // no heartbeat task found
                $this->_getSession()->addError($this->__('No heartbeat task found. Check if cron is configured correctly. (<a href="%s">See Instructions</a>)', $this->getUrl('adminhtml/instructions/index')));
            } else {
                $timespan = Mage::helper('aoe_scheduler')->dateDiff($lastHeartbeat);
                if ($timespan <= 5 * 60) {
                    $this->_getSession()->addSuccess($this->__('Scheduler is working. (Last heart beat: %s minute(s) ago)', round($timespan / 60)));
                } elseif ($timespan > 5 * 60 && $timespan <= 60 * 60) {
                    // heartbeat wasn't executed in the last 5 minutes. Heartbeat schedule could have been modified to not run every five minutes!
                    $this->_getSession()->addNotice($this->__('Last heartbeat is older than %s minutes.', round($timespan / 60)));
                } else {
                    // everything ok
                    $this->_getSession()->addError($this->__('Last heartbeat is older than one hour. Please check your settings and your configuration!'));
                }
            }
        }
    }

    /**
     * Generate schedule now
     *
     * @return void
     */
    public function generateScheduleAction()
    {
        Mage::app()->removeCache(Mage_Cron_Model_Observer::CACHE_KEY_LAST_SCHEDULE_GENERATE_AT);

        /* @var Aoe_Scheduler_Model_ScheduleManager $scheduleManager */
        $scheduleManager = Mage::getModel('aoe_scheduler/scheduleManager');
        $scheduleManager->generateSchedules();

        $this->_getSession()->addSuccess($this->__('Generated schedule'));
        $this->_redirect('*/*/index');
    }
}
