<?php

/**
 * Timeline block
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Block_Adminhtml_Timeline extends Mage_Adminhtml_Block_Widget_Container
{

    /**
     * @var int amount of seconds per pixel
     */
    protected $zoom = 15;

    /**
     * @var int starttime
     */
    protected $starttime;

    /**
     * @var int endtime
     */
    protected $endtime;

    /**
     * @var array schedules
     */
    protected $schedules = array();


    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_headerText = $this->__('Scheduler Timeline');
        $this->loadSchedules();
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
     * Return the last full houd
     *
     * @param int $timestamp
     * @return int
     */
    protected function hourFloor($timestamp)
    {
        return mktime(date('H', $timestamp), 0, 0, date('n', $timestamp), date('j', $timestamp), date('Y', $timestamp));
    }


    /**
     * Returns the next full hour
     *
     * @param int $timestamp
     * @return int
     */
    protected function hourCeil($timestamp)
    {
        return mktime(date('H', $timestamp) + 1, 0, 0, date('n', $timestamp), date('j', $timestamp), date('Y', $timestamp));
    }


    /**
     * Load schedules
     *
     * @return void
     */
    protected function loadSchedules()
    {
        /* @var Mage_Cron_Model_Resource_Schedule_Collection $collection */
        $collection = Mage::getModel('cron/schedule')->getCollection();

        $minDate = null;
        $maxDate = null;

        foreach ($collection as $schedule) {
            /* @var Aoe_Scheduler_Model_Schedule $schedule */
            $startTime = $schedule->getStarttime();
            if (empty($startTime)) {
                continue;
            }
            $minDate = is_null($minDate) ? $startTime : min($minDate, $startTime);
            $maxDate = is_null($maxDate) ? $startTime : max($maxDate, $startTime);
            $this->schedules[$schedule->getJobCode()][] = $schedule;
        }

        $this->starttime = $this->hourFloor(strtotime($minDate));
        $this->endtime = $this->hourCeil(strtotime($maxDate));
    }


    /**
     * Get timeline panel width
     *
     * @return int
     */
    public function getTimelinePanelWidth()
    {
        return ($this->endtime - $this->starttime) / $this->zoom;
    }


    /**
     * Get "now" line
     *
     * @return float
     */
    public function getNowline()
    {
        return (time() - $this->starttime) / $this->zoom;
    }


    /**
     * Get all available job codes
     *
     * @return array
     */
    public function getAvailableJobCodes()
    {
        return array_keys($this->schedules);
    }


    /**
     * Get schedules for given code
     *
     * @param string $code
     * @return array
     */
    public function getSchedulesForCode($code)
    {
        return $this->schedules[$code];
    }


    /**
     * Get starttime
     *
     * @return int
     */
    public function getStarttime()
    {
        return $this->starttime;
    }


    /**
     * Get endtime
     *
     * @return int
     */
    public function getEndtime()
    {
        return $this->endtime;
    }


    /**
     * Get attributes for div representing a gantt element
     *
     * @param Aoe_Scheduler_Model_Schedule $schedule
     * @return string
     */
    public function getGanttDivAttributes(Aoe_Scheduler_Model_Schedule $schedule)
    {

        if ($schedule->getStatus() == Aoe_Scheduler_Model_Schedule::STATUS_RUNNING) {
            $duration = time() - strtotime($schedule->getStarttime());
        } else {
            $duration = $schedule->getDuration() ? $schedule->getDuration() : 0;
        }
        $duration = $duration / $this->zoom;
        $duration = ceil($duration / 4) * 4 - 1; // round to numbers dividable by 4, then remove 1 px border
        $duration = max($duration, 3);

        $offset = (strtotime($schedule->getStarttime()) - $this->starttime) / $this->zoom;

        if ($offset < 0) { // cut bar
            $duration += $offset;
            $offset = 0;
        }

        $result = sprintf(
            '<div class="task %s" id="id_%s" style="width: %spx; left: %spx;" ></div>',
            $schedule->getStatus(),
            $schedule->getScheduleId(),
            $duration,
            $offset
        );

        if ($schedule->getStatus() == Aoe_Scheduler_Model_Schedule::STATUS_RUNNING) {
            $offset += $duration;

            $duration = strtotime($schedule->getEta()) - time();
            $duration = $duration / $this->zoom;

            $result = sprintf(
                '<div class="estimation" style="width: %spx; left: %spx;" ></div>',
                $duration,
                $offset
            ) . $result;
        }

        return $result;
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
