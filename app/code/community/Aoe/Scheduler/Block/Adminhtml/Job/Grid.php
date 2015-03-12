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
        /** @var Aoe_Scheduler_Model_Resource_Job_Collection $collection */
        $collection = Mage::getSingleton('aoe_scheduler/job')->getCollection();
        $this->setCollection($collection);
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
        $this->getMassactionBlock()->addItem(
            'schedule',
            array(
                'label' => $this->__('Schedule now'),
                'url'   => $this->getUrl('*/*/scheduleNow'),
            )
        );
        if (Mage::getStoreConfig('system/cron/enableRunNow')) {
            $this->getMassactionBlock()->addItem(
                'run',
                array(
                    'label' => $this->__('Run now'),
                    'url'   => $this->getUrl('*/*/runNow'),
                )
            );
        }
        $this->getMassactionBlock()->addItem(
            'disable',
            array(
                'label' => $this->__('Disable'),
                'url'   => $this->getUrl('*/*/disable'),
            )
        );
        $this->getMassactionBlock()->addItem(
            'enable',
            array(
                'label' => $this->__('Enable'),
                'url'   => $this->getUrl('*/*/enable'),
            )
        );
        return $this;
    }


    /**
     * Preparation of the requested columns of the grid
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'job_code',
            array(
                'header'   => $this->__('Job code'),
                'index'    => 'job_code',
                'sortable' => false,
            )
        );

        $this->addColumn(
            'name',
            array(
                'header'   => $this->__('Name'),
                'index'    => 'name',
                'sortable' => false,
            )
        );

        $this->addColumn(
            'short_description',
            array(
                'header'   => $this->__('Short Description'),
                'index'    => 'short_description',
                'sortable' => false,
            )
        );

        $this->addColumn(
            'schedule_cron_expr',
            array(
                'header'         => $this->__('Cron expression'),
                'index'          => 'schedule_cron_expr',
                'sortable'       => false,
                'frame_callback' => array($this, 'decorateCronExpression'),
            )
        );
        $this->addColumn(
            'run_model',
            array(
                'header'   => $this->__('Run model'),
                'index'    => 'run_model',
                'sortable' => false,
            )
        );
        $this->addColumn(
            'parameters',
            array(
                'header'         => $this->__('Parameters'),
                'index'          => 'parameters',
                'sortable'       => false,
                'frame_callback' => array($this, 'decorateTrim'),
            )
        );
        $this->addColumn(
            'groups',
            array(
                'header'         => $this->__('Groups'),
                'index'          => 'groups',
                'sortable'       => false,
                'frame_callback' => array($this, 'decorateTrim'),
            )
        );
        $this->addColumn(
            'type',
            array(
                'header'         => $this->__('Type'),
                'sortable'       => false,
                'frame_callback' => array($this, 'decorateType'),
            )
        );
        $this->addColumn(
            'is_active',
            array(
                'header'         => $this->__('Status'),
                'index'          => 'is_active',
                'sortable'       => false,
                'frame_callback' => array($this, 'decorateStatus'),
            )
        );
        return parent::_prepareColumns();
    }


    /**
     * Decorate status column values
     *
     * @param $value
     *
     * @return string
     */
    public function decorateStatus($value)
    {
        $cell = sprintf(
            '<span class="grid-severity-%s"><span>%s</span></span>',
            $value ? 'notice' : 'critical',
            $this->__($value ? 'Enabled' : 'Disabled')
        );
        return $cell;
    }


    /**
     * Decorate cron expression
     *
     * @param                         $value
     * @param Aoe_Scheduler_Model_Job $job
     *
     * @return string
     */
    public function decorateCronExpression($value, Aoe_Scheduler_Model_Job $job)
    {
        return $job->getCronExpression();
    }


    /**
     * Decorate cron expression
     *
     * @param $value
     *
     * @return string
     */
    public function decorateTrim($value)
    {
        return sprintf('<span title="%s">%s</span>', $value, mb_strimwidth($value, 0, 40, "..."));
    }


    /**
     * Decorate cron expression
     *
     * @param                         $value
     * @param Aoe_Scheduler_Model_Job $job
     *
     * @return string
     */
    public function decorateType($value, Aoe_Scheduler_Model_Job $job)
    {
        return $job->getType();
    }

    /**
     * Row click url
     *
     * @param object $row
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('job_code' => $row->getJobCode()));
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
