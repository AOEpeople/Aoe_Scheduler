<?php
/**
 * Scheduler controller
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Adminhtml_SchedulerController extends Aoe_Scheduler_Controller_AbstractController
{
    /**
     * Index action
     *
     * @return void
     */
    public function indexAction()
    {
        $this->_initAction()
            ->_addBreadcrumb($this->__('List View'), $this->__('List View'))
            ->_title($this->__('List View'))
            ->renderLayout();
    }

    /**
     * Mass action: delete
     *
     * @return void
     */
    public function deleteAction()
    {
        $ids = $this->getRequest()->getParam('schedule_ids');
        foreach ($ids as $id) {
            Mage::getModel('cron/schedule')->load($id)
                ->delete();
        }
        $message = $this->__('Deleted task(s) "%s"', implode(', ', $ids));
        $this->_getSession()->addSuccess($message);
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
            $schedule = Mage::getModel('cron/schedule'); /* @var $schedule Aoe_Scheduler_Model_Schedule */
            $schedule->load($id)->requestKill();
        }
        $message = $this->__('Kill requests saved for task(s) "%s" (will be killed via cron)', implode(', ', $ids));
        $this->_getSession()->addSuccess($message);
        if ($logFile = Mage::getStoreConfig('system/cron/logFile')) {
            Mage::log($message, null, $logFile);
        }
        $this->_redirect('*/*/index');
    }

    /**
     * Show the misconfigured cron user warning message again
     */
    public function showUserWarningAction()
    {
        Mage::getModel('core/config')
            ->saveConfig(Aoe_Scheduler_Helper_Data::XML_PATH_SHOW_WRONG_USER_MSG, 1);
        $this->_getSession()->addSuccess($this->__('You will be alerted about misconfigured cron users again.'));
        $this->_redirectReferer();
    }

    /**
     * Don't show the misconfigured cron user warning message any more
     */
    public function hideUserWarningAction()
    {
        Mage::getModel('core/config')
            ->saveConfig(Aoe_Scheduler_Helper_Data::XML_PATH_SHOW_WRONG_USER_MSG, 0);
        $this->_getSession()->addSuccess(
            $this->__(
                'We won\'t alert you about misconfigured cron users again. You can turn it back on in the scheduler configuration or by <a href="%s">clicking here</a>.',
                $this->getUrl('*/scheduler/showUserWarning')
            )
        );
        $this->_redirectReferer();
    }

    /**
     * Set the configured user to what was probably suggested
     */
    public function setConfiguredUserAction()
    {
        $user = $this->getRequest()->getParam('user');
        Mage::getModel('core/config')
            ->saveConfig(Aoe_Scheduler_Helper_Data::XML_PATH_CRON_USER, (string) $user);

        $this->_getSession()->addSuccess($this->__('Configured cron user updated to be "%s"', $user));
        $this->_redirectReferer();
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
