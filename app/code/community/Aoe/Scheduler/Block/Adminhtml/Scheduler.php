<?php

/**
 * Scheduler Block
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Block_Adminhtml_Scheduler extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Constructor for Scheduler Adminhtml Block
     */
    public function __construct()
    {
        $this->_blockGroup = 'aoe_scheduler';
        $this->_controller = 'adminhtml_scheduler';
        $this->_headerText = $this->__('Scheduled tasks');
        parent::__construct();
    }


    /**
     * Prepare layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->removeButton('add');
        $this->_addButton(
            'add_new',
            ['label'   => $this->__('Generate Schedule'), 'onclick' => "setLocation('{$this->getUrl('*/*/generateSchedule')}')"]
        );
        $this->_addButton(
            'configure',
            ['label'   => $this->__('Cron Configuration'), 'onclick' => "setLocation('{$this->getUrl('adminhtml/system_config/edit', ['section' => 'system'])}#system_cron')"]
        );
        return parent::_prepareLayout();
    }


    /**
     * Returns the CSS class for the header
     *
     * Usually 'icon-head' and a more precise class is returned. We return
     * only an empty string to avoid spacing on the left of the header as we
     * don't have an icon.
     *
     * @return string
     */
    public function getHeaderCssClass()
    {
        return '';
    }
}
