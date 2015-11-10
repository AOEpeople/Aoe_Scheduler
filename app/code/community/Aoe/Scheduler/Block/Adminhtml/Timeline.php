<?php

/**
 * Timeline block
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Block_Adminhtml_Timeline extends Mage_Adminhtml_Block_Widget_Container
{

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_headerText = $this->__('Scheduler Timeline');
        parent::_construct();
    }

    /**
     * Prepare layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->removeButton('add');
        $this->_addButton('auto_refresh', array(
            'label' => $this->__('Auto Refresh'),
            'title' => $this->__('Automatically refresh data every minute'),
            'onclick' => "Scheduler.Timeline.toggleAutoRefresh()",
            'class' => 'disabled autorefresh'
        ));
        $this->_addButton('refresh', array(
            'label' => $this->__('Refresh Now'),
            'onclick' => "Scheduler.Timeline.refresh()",
        ));
        $this->_addButton('add_new', array(
            'label' => $this->__('Generate Schedule'),
            'onclick' => "setLocation('{$this->getUrl('*/*/generateSchedule')}')",
        ));
        $this->_addButton('configure', array(
            'label' => $this->__('Cron Configuration'),
            'onclick' => "setLocation('{$this->getUrl('adminhtml/system_config/edit', array('section' => 'system'))}#system_cron')",
        ));
        return parent::_prepareLayout();
    }

    /**
     * Check if symlinks are allowed
     *
     * @return string
     */
    public function _toHtml()
    {
        $html = parent::_toHtml();
        if (!$html && !Mage::getStoreConfigFlag('dev/template/allow_symlink')) {
            $url = $this->getUrl('adminhtml/system_config/edit', array('section' => 'dev')) . '#dev_template';
            $html = $this->__('Warning: You installed Aoe_Scheduler using symlinks (e.g. via modman), but forgot to allow symlinks for template files! Please go to <a href="%s">System > Configuration > Advanced > Developer > Template Settings</a> and set "Allow Symlinks" to "yes"', $url);
        }
        return $html;
    }
}
