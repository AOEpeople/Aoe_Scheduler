<?php

require_once Mage::getModuleDir('controllers', 'Aoe_Scheduler') . '/Adminhtml/AbstractController.php';

/**
 * Cron controller
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Adminhtml_CronController extends Aoe_Scheduler_Adminhtml_AbstractController
{

    /**
     * Mass action: disable
     *
     * @return void
     */
    public function disableAction()
    {
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
    public function enableAction()
    {
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
    public function scheduleNowAction()
    {
        $codes = $this->getRequest()->getParam('codes');
        if (is_array($codes)) {
            foreach ($codes as $key) {
                Mage::getModel('cron/schedule')/* @var Aoe_Scheduler_Model_Schedule */
                    ->setJobCode($key)
                    ->schedule()
                    ->save();
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Scheduled "%s"', $key));
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * Mass action: run now
     *
     * @return void
     */
    public function runNowAction()
    {
        if (!Mage::getStoreConfig('system/cron/enableRunNow')) {
            Mage::throwException("'Run now' disabled by configuration (system/cron/enableRunNow)");
        }
        $codes = $this->getRequest()->getParam('codes');
        if (is_array($codes)) {
            foreach ($codes as $key) {
                /* @var Aoe_Scheduler_Model_Schedule $schedule */
                $schedule = Mage::getModel('cron/schedule')->setJobCode($key);
                try {
                    $schedule->runNow(false); // without trying to lock the job
                } catch (Exception $e) {
                    $schedule->setStatus(Mage_Cron_Model_Schedule::STATUS_ERROR);
                    $schedule->setMessages($e->__toString());

                    // Aoe_Scheduler: additional handling:
                    Mage::dispatchEvent('cron_' . $schedule->getJobCode() . '_exception', array('schedule' => $schedule, 'exception' => $e));
                    Mage::dispatchEvent('cron_exception', array('schedule' => $schedule, 'exception' => $e));
                    Mage::helper('aoe_scheduler')->sendErrorMail($schedule, $e->__toString());
                }
                $schedule->save();

                $messages = $schedule->getMessages();

                if ($schedule->getStatus() == Mage_Cron_Model_Schedule::STATUS_SUCCESS) {
                    Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Ran "%s" (Duration: %s sec)', $key, intval($schedule->getDuration())));
                    if ($messages) {
                        Mage::getSingleton('adminhtml/session')->addSuccess($this->__('"%s" messages:<pre>%s</pre>', $key, $messages));
                    }
                } else {
                    Mage::getSingleton('adminhtml/session')->addError($this->__('Error while running "%s"', $key));
                    if ($messages) {
                        Mage::getSingleton('adminhtml/session')->addError($this->__('"%s" messages:<pre>%s</pre>', $key, $messages));
                    }
                }

            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * Acl checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/aoe_scheduler/aoe_scheduler_cron');
    }

}

