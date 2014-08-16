<?php

/**
 * Block: Job grid
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Block_Adminhtml_Job_Grid extends Mage_Adminhtml_Block_Widget_Grid
{


    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('job_grid');
        $this->_filterVisibility = false;
        $this->_pagerVisibility = false;
    }


    /**
     * Preparation of the data that is displayed by the grid.
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $jobFactory = Mage::getModel('aoe_scheduler/job_factory'); /* @var $jobFactory Aoe_Scheduler_Model_Job_Factory */
        $this->setCollection($jobFactory->getAllJobs());
        return parent::_prepareCollection();
    }


    /**
     * Add mass-actions to grid
     *
     * @return $this
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('codes');
        $this->getMassactionBlock()->addItem('schedule', array(
            'label' => Mage::helper('aoe_scheduler')->__('Schedule now'),
            'url' => $this->getUrl('*/*/scheduleNow'),
        ));
        if (Mage::getStoreConfig('system/cron/enableRunNow')) {
            $this->getMassactionBlock()->addItem('run', array(
                'label' => Mage::helper('aoe_scheduler')->__('Run now'),
                'url' => $this->getUrl('*/*/runNow'),
            ));
        }
        $this->getMassactionBlock()->addItem('disable', array(
            'label' => Mage::helper('aoe_scheduler')->__('Disable'),
            'url' => $this->getUrl('*/*/disable'),
        ));
        $this->getMassactionBlock()->addItem('enable', array(
            'label' => Mage::helper('aoe_scheduler')->__('Enable'),
            'url' => $this->getUrl('*/*/enable'),
        ));
        return $this;
    }


    /**
     * Preparation of the requested columns of the grid
     *
     * @return $this
     */
    protected function _prepareColumns()
    {

        $this->addColumn('job_code', array(
            'header' => Mage::helper('aoe_scheduler')->__('Job code'),
            'index' => 'job_code',
            'sortable' => false,
        ));
        $this->addColumn('schedule_cron_expr', array(
            'header' => Mage::helper('aoe_scheduler')->__('Cron expression'),
            'index' => 'schedule_cron_expr',
            'sortable' => false,
            'frame_callback' => array($this, 'decorateCronExpression'),
        ));
        $this->addColumn('run_model', array(
            'header' => Mage::helper('aoe_scheduler')->__('Run model'),
            'index' => 'run_model',
            'sortable' => false,
        ));
        $this->addColumn('parameter', array(
            'header' => Mage::helper('aoe_scheduler')->__('Parameter'),
            'index' => 'parameter',
            'sortable' => false,
            'frame_callback' => array($this, 'decorateParameter'),
        ));
        $this->addColumn('type', array(
            'header' => Mage::helper('aoe_scheduler')->__('Type'),
            'sortable' => false,
            'frame_callback' => array($this, 'decorateType'),
        ));
        $this->addColumn('is_active', array(
            'header' => Mage::helper('aoe_scheduler')->__('Status'),
            'index' => 'is_active',
            'sortable' => false,
            'frame_callback' => array($this, 'decorateStatus'),
        ));
        return parent::_prepareColumns();
    }


    /**
     * Decorate status column values
     *
     * @param $value
     * @return string
     */
    public function decorateStatus($value)
    {
        $cell = sprintf('<span class="grid-severity-%s"><span>%s</span></span>',
            $value ? 'notice' : 'critical',
            Mage::helper('aoe_scheduler')->__($value ? 'Enabled' : 'Disabled')
        );
        return $cell;
    }


    /**
     * Decorate cron expression
     *
     * @param $value
     * @param Aoe_Scheduler_Model_Job_Abstract $job
     * @return string
     */
    public function decorateCronExpression($value, Aoe_Scheduler_Model_Job_Abstract $job)
    {
        return $job->getCronExpression();
    }


    /**
     * Decorate cron expression
     *
     * @param $value
     * @return string
     */
    public function decorateParameter($value)
    {
        return sprintf('<span title="%s">%s</span>', $value, mb_strimwidth($value, 0, 40, "..."));
    }



    /**
     * Decorate cron expression
     *
     * @param $value
     * @param Aoe_Scheduler_Model_Job_Abstract $job
     * @return string
     */
    public function decorateType($value, Aoe_Scheduler_Model_Job_Abstract $job)
    {
        return $job->getType();
    }

    /**
     * Row click url
     *
     * @param object $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('job_code' => $row->getId()));
    }

    /**
     * Helper function to receive grid functionality urls for current grid
     *
     * @return string Requested URL
     */
    public function getGridUrl()
    {
        return $this->getUrl('adminhtml/job/index', array('_current' => true));
    }

}