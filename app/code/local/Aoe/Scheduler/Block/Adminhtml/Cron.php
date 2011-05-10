<?php
class Aoe_Scheduler_Block_Adminhtml_Cron extends Mage_Adminhtml_Block_Widget_Grid_Container {
	
    /**
     * Constructor for Contact Adminhtml Block
     */
    public function __construct() {
        $this->_blockGroup     = 'aoe_scheduler';
        $this->_controller     = 'adminhtml_cron';
        $this->_headerText     = Mage::helper('aoe_scheduler')->__('Available tasks');
        
        parent::__construct();
    }    
    
    
    
    /**
     * Prepare layout
     * 
     * @return Aoe_Scheduler_Block_Adminhtml_Cron
     */
    protected function _prepareLayout() {
    	$this->removeButton('add');
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
    public function getHeaderCssClass() {
        return '';
    }
    
}
