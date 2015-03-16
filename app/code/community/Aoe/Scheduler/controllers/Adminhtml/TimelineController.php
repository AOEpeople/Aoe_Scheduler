<?php
/**
 * Timeline controller
 *
 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
 */
class Aoe_Scheduler_Adminhtml_TimelineController extends Aoe_Scheduler_Controller_AbstractController
{

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
