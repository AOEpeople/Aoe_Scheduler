<?php
/**
 * Timeline controller
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Adminhtml_TimelineController extends Aoe_Scheduler_Controller_AbstractController
{
    /**
     * Index action
     *
     * @return void
     */
    public function indexAction()
    {
        $this->_initAction()
            ->_addBreadcrumb($this->__('Timeline View'), $this->__('Timeline View'))
            ->_title($this->__('Timeline View'))
            ->renderLayout();
    }

    /**
     * Acl checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/aoe_scheduler/aoe_scheduler_timeline');
    }
}
