<?php
/**
 * Instructions controller
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Adminhtml_InstructionsController extends Aoe_Scheduler_Controller_AbstractController
{
    /**
     * Index action
     *
     * @return void
     */
    public function indexAction()
    {
        $this->_initAction()
            ->_addBreadcrumb($this->__('Instructions'), $this->__('Instructions'))
            ->_title($this->__('Instructions'))
            ->renderLayout();
    }

    /**
     * Acl checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/aoe_scheduler/aoe_scheduler_instructions');
    }
}
