<?php

require_once Mage::getModuleDir('controllers', 'Aoe_Scheduler') . '/Adminhtml/AbstractController.php';

/**
 * Scheduler controller
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Adminhtml_SchedulerController extends Aoe_Scheduler_Adminhtml_AbstractController
{

    /**
     * Mass action: delete
     *
     * @return void
     */
    public function deleteAction()
    {
        $ids = $this->getRequest()->getParam('schedule_ids');
        foreach ($ids as $id) {
            $schedule = Mage::getModel('cron/schedule')/* @var $schedule Aoe_Scheduler_Model_Schedule */
                ->load($id)
                ->delete();
        }
        $message = $this->__('Deleted task(s) "%s"', implode(', ', $ids));
        Mage::getSingleton('adminhtml/session')->addSuccess($message);
        if ($logFile = Mage::getStoreConfig('system/cron/logFile')) {
            Mage::log($message, null, $logFile);
        }
        $this->_redirect('*/*/index');
    }

    /**
     * Mass action: kill
     *
     * @return void
     */
    public function killAction()
    {
        $ids = $this->getRequest()->getParam('schedule_ids');
        foreach ($ids as $id) {
            $schedule = Mage::getModel('cron/schedule')/* @var $schedule Aoe_Scheduler_Model_Schedule */
                ->load($id)
                ->setKillRequest(strftime('%Y-%m-%d %H:%M:%S', time()))
                ->save();
        }
        $message = $this->__('Kill requests saved for task(s) "%s" (will be killed via cron)', implode(', ', $ids));
        Mage::getSingleton('adminhtml/session')->addSuccess($message);
        if ($logFile = Mage::getStoreConfig('system/cron/logFile')) {
            Mage::log($message, null, $logFile);
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
        return Mage::getSingleton('admin/session')->isAllowed('system/aoe_scheduler/aoe_scheduler_scheduler');
    }

}
