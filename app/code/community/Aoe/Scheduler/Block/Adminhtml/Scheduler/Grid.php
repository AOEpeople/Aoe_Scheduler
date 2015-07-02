<?php

/**
 * Block: Scheduler Grid
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Block_Adminhtml_Scheduler_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Constructor. Set basic parameters
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('scheduler_grid');
        $this->setUseAjax(false);
        $this->setDefaultSort('scheduled_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }


    /**
     * Preparation of the data that is displayed by the grid.
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        /** @var Mage_Cron_Model_Resource_Schedule_Collection $collection */
        $collection = Mage::getModel('cron/schedule')->getCollection();
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
        $this->setMassactionIdField('schedule_id');
        $this->getMassactionBlock()->setFormFieldName('schedule_ids');
        $this->getMassactionBlock()->addItem(
            'delete',
            array(
                'label' => $this->__('Delete'),
                'url'   => $this->getUrl('*/*/delete'),
            )
        );
        $this->getMassactionBlock()->addItem(
            'kill',
            array(
                'label' => $this->__('Kill'),
                'url'   => $this->getUrl('*/*/kill'),
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
        $viewHelper = $this->helper('aoe_scheduler/data');

        $this->addColumn(
            'schedule_id',
            array(
                'header' => $this->__('Id'),
                'index'  => 'schedule_id',
            )
        );
        $this->addColumn(
            'job_code',
            array(
                'header'  => $this->__('Job'),
                'index'   => 'job_code',
                'type'    => 'options',
                'options' => Mage::getSingleton('aoe_scheduler/job')->getCollection()->toOptionHash('job_code', 'name')
            )
        );
        $this->addColumn(
            'created_at',
            array(
                'header'         => $this->__('Created'),
                'index'          => 'created_at',
                'frame_callback' => array($viewHelper, 'decorateTimeFrameCallBack')
            )
        );
        $this->addColumn(
            'scheduled_at',
            array(
                'header'         => $this->__('Scheduled'),
                'index'          => 'scheduled_at',
                'frame_callback' => array($viewHelper, 'decorateTimeFrameCallBack')
            )
        );
        $this->addColumn(
            'executed_at',
            array(
                'header'         => $this->__('Executed'),
                'index'          => 'executed_at',
                'frame_callback' => array($viewHelper, 'decorateTimeFrameCallBack')
            )
        );
        $this->addColumn(
            'last_seen',
            array(
                'header'         => $this->__('Last seen'),
                'index'          => 'last_seen',
                'frame_callback' => array($viewHelper, 'decorateTimeFrameCallBack')
            )
        );
        $this->addColumn(
            'eta',
            array(
                'header'         => $this->__('ETA'),
                'index'          => 'eta',
                'frame_callback' => array($viewHelper, 'decorateTimeFrameCallBack')
            )
        );
        $this->addColumn(
            'finished_at',
            array(
                'header'         => $this->__('Finished'),
                'index'          => 'finished_at',
                'frame_callback' => array($viewHelper, 'decorateTimeFrameCallBack')
            )
        );
        $this->addColumn(
            'messages',
            array(
                'header'         => $this->__('Messages'),
                'index'          => 'messages',
                'frame_callback' => array($this, 'decorateMessages')
            )
        );
        $this->addColumn(
            'host',
            array(
                'header' => $this->__('Host'),
                'index'  => 'host',
            )
        );
        $this->addColumn(
            'pid',
            array(
                'header' => $this->__('Pid'),
                'index'  => 'pid',
                'width' => '50',
            )
        );
        $this->addColumn(
            'status',
            array(
                'header'         => $this->__('Status'),
                'index'          => 'status',
                'frame_callback' => array($viewHelper, 'decorateStatus'),
                'type'           => 'options',
                'options'        => Mage::getSingleton('cron/schedule')->getStatuses()
            )
        );

        return parent::_prepareColumns();
    }


    /**
     * Decorate message
     *
     * @param string                       $value
     * @param Aoe_Scheduler_Model_Schedule $row
     *
     * @return string
     */
    public function decorateMessages($value, Aoe_Scheduler_Model_Schedule $row)
    {
        $return = '';
        if (!empty($value)) {
            $return .= '<a href="#" onclick="$(\'messages_' . $row->getScheduleId() . '\').toggle(); return false;">' . $this->__('Message') . '</a>';
            $return .= '<div class="schedule-message" id="messages_' . $row->getScheduleId() . '" style="display: none; width: 300px; overflow: auto; font-size: small;"><pre>' . $value . '</pre></div>';
        }
        return $return;
    }


    /**
     * Helper function to do after load modifications
     *
     * @return void
     */
    protected function _afterLoadCollection()
    {
        $this->getCollection()->walk('afterLoad');
        parent::_afterLoadCollection();
    }


    /**
     * Helper function to add store filter condition
     *
     * @param Mage_Core_Model_Mysql4_Collection_Abstract $collection Data collection
     * @param Mage_Adminhtml_Block_Widget_Grid_Column    $column     Column information to be filtered
     *
     * @return void
     */
    protected function _filterStoreCondition($collection, $column)
    {
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
    public function getGridUrl()
    {
        return $this->getUrl('adminhtml/scheduler/index', array('_current' => true));
    }
}
