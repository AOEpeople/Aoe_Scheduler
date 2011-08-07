<?php

/**
 * Block: Cron grid
 *
 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
 */
class Aoe_Scheduler_Block_Adminhtml_Cron_Grid extends Mage_Adminhtml_Block_Widget_Grid {



	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->setId('cron_grid');
		$this->_filterVisibility = false;
		$this->_pagerVisibility  = false;
	}



	/**
	 * Preparation of the data that is displayed by the grid.
	 *
	 * @return Aoe_Scheduler_Block_Adminhtml_Cron_Grid Self
	 */
	protected function _prepareCollection() {
		$collection = Mage::getModel('aoe_scheduler/collection_crons');
		$this->setCollection($collection);
		return parent::_prepareCollection();
	}



	/**
	 * Add mass-actions to grid
	 *
	 * @return Aoe_Scheduler_Block_Adminhtml_Cron_Grid
	 */
	protected function _prepareMassaction() {
		$this->setMassactionIdField('id');
		$this->getMassactionBlock()->setFormFieldName('codes');
		$this->getMassactionBlock()->addItem('schedule', array(
			'label'         => Mage::helper('aoe_scheduler')->__('Schedule now'),
			'url'           => $this->getUrl('*/*/scheduleNow'),
		));
		$this->getMassactionBlock()->addItem('run', array(
			'label'    => Mage::helper('aoe_scheduler')->__('Run now'),
			'url'      => $this->getUrl('*/*/runNow'),
		));
		$this->getMassactionBlock()->addItem('disable', array(
			'label'    => Mage::helper('aoe_scheduler')->__('Disable'),
			'url'      => $this->getUrl('*/*/disable'),
		));
		$this->getMassactionBlock()->addItem('enable', array(
			'label'    => Mage::helper('aoe_scheduler')->__('Enable'),
			'url'      => $this->getUrl('*/*/enable'),
		));
		return $this;
	}



	/**
	 * Preparation of the requested columns of the grid
	 *
	 * @return Aoe_Scheduler_Block_Adminhtml_Cron_Grid Self
	 */
	protected function _prepareColumns() {
		$this->addColumn('id', array (
			'header' => Mage::helper('aoe_scheduler')->__('Code'),
			'index' => 'id',
			'sortable'  => false,
		));
		$this->addColumn('cron_expr', array (
			'header' => Mage::helper('aoe_scheduler')->__('Cron Expression'),
			'index' => 'cron_expr',
			'sortable'  => false,
		));
		$this->addColumn('model', array (
			'header' => Mage::helper('aoe_scheduler')->__('Model'),
			'index' => 'model',
			'sortable'  => false,
		));
		$this->addColumn('status', array (
			'header' => Mage::helper('aoe_scheduler')->__('Status'),
			'index' => 'status',
			'sortable'  => false,
			'frame_callback' => array($this, 'decorateStatus'),
		));
		return parent::_prepareColumns();
	}



	/**
	 * Decorate status column values
	 *
	 * @return string
	 */
	public function decorateStatus($value) {
		$cell = sprintf('<span class="grid-severity-%s"><span>%s</span></span>',
			($value == Aoe_Scheduler_Model_Configuration::STATUS_DISABLED) ? 'critical' : 'notice',
			Mage::helper('aoe_scheduler')->__($value)
		);
		return $cell;
	}



	/**
	 * Helper function to add store filter condition
	 *
	 * @param Mage_Core_Model_Mysql4_Collection_Abstract $collection Data collection
	 * @param Mage_Adminhtml_Block_Widget_Grid_Column $column Column information to be filtered
	 * @return void
	 */
	protected function _filterStoreCondition($collection, $column) {
		if (!$value = $column->getFilter()->getValue()) {
			return;
		}
		$this->getCollection()->addStoreFilter($value);
	}



	/**
	 * Helper function to receive grid functionality urls for current grid
	 *
	 * @return string Requested URL
	 */
	public function getGridUrl() {
		return $this->getUrl('adminhtml/scheduler/cron', array('_current' => true));
	}

}
