<?php
class Aoe_Scheduler_Block_Adminhtml_Timeline extends Mage_Adminhtml_Block_Widget_Container {

	/**
	 * @var int amount of seconds per pixel
	 */
	protected $zoom = 15;

	protected $starttime;

	protected $endtime;

	protected $currentTimeStamp;

	protected $nowLine;

	protected $minDate;

	protected $maxDate;

	protected $schedules = array();

	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function _construct() {

		$this->_headerText = Mage::helper('aoe_scheduler')->__('Scheduler Timeline');

		parent::_construct();

		$this->loadSchedules();

		$this->starttime = $this->hourFloor(strtotime($this->minDate));
		$this->endtime = $this->hourCeil(strtotime($this->maxDate));

		// TODO: add max/min time (+-24h?)

		$this->nowLine = (time() - $this->starttime) / $this->zoom;
	}

	/**
	 * Prepare layout
	 *
	 * @return Aoe_Scheduler_Block_Adminhtml_Cron
	 */
	protected function _prepareLayout() {
		$this->removeButton('add');
		$this->_addButton('add_new', array(
			'label'   => Mage::helper('aoe_scheduler')->__('Generate Schedule'),
			'onclick' => "setLocation('{$this->getUrl('*/*/generateSchedule')}')",
		));
		$this->_addButton('configure', array(
			'label'   => Mage::helper('aoe_scheduler')->__('Cron Configuration'),
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
	protected function hourFloor($timestamp) {
		return mktime(date('H', $timestamp), 0, 0, date('n', $timestamp), date('j', $timestamp), date('Y', $timestamp));
	}

	/**
	 * Returns the next full hour
	 *
	 * @param int $timestamp
	 * @return int
	 */
	protected function hourCeil($timestamp) {
		return mktime(date('H', $timestamp)+1, 0, 0, date('n', $timestamp), date('j', $timestamp), date('Y', $timestamp));
	}

	/**
	 * Load schedules
	 *
	 * @return void
	 */
	protected function loadSchedules() {
		$collection = Mage::getModel('cron/schedule')->getCollection(); /* @var $collection Mage_Cron_Model_Mysql4_Schedule_Collection */
		// TODO: at max age
		foreach ($collection as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Schedule */
			$startTime = $schedule->getStarttime();
			$this->minDate = is_null($this->minDate) ? $startTime : min($this->minDate, $startTime);
			$this->maxDate = is_null($this->maxDate) ? $startTime : max($this->maxDate, $startTime);
			$this->schedules[$schedule->getJobCode()][] = $schedule;
		}
	}

	/**
	 * Get timeline panel width
	 *
	 * @return int
	 */
	public function getTimelinePanelWidth() {
		return ($this->endtime - $this->starttime) / $this->zoom;
	}

	/**
	 * Get all available job codes
	 *
	 * @return array
	 */
	public function getAvailableJobCodes() {
		return array_keys($this->schedules);
	}

	/**
	 * Get schedules for given code
	 *
	 * @param string $code
	 * @return array
	 */
	public function getSchedulesForCode($code) {
		return $this->schedules[$code];
	}

	/**
	 * Get starttime
	 *
	 * @return int
	 */
	public function getStarttime() {
		return $this->starttime;
	}

	/**
	 * Get endtime
	 *
	 * @return int
	 */
	public function getEndtime() {
		return $this->endtime;
	}

	/**
	 * Get "now" line
	 *
	 * @return int
	 */
	public function getNowline() {
		return $this->nowLine;
	}

	/**
	 * Get attributes for div representing a gantt element
	 *
	 * @param Aoe_Scheduler_Model_Schedule $schedule
	 * @return string
	 */
	public function getGanttDivAttributes(Aoe_Scheduler_Model_Schedule $schedule) {

		$duration = $schedule->getDuration() ? $schedule->getDuration() : 0;
		$duration = $duration / $this->zoom;
		$duration = ceil($duration / 4) * 4 - 1; // round to numbers dividable by 4, then remove 1 px border
		$duration = max($duration, 3);

		$offset = (strtotime($schedule->getStarttime()) - $this->starttime) / $this->zoom;

		if ($offset < 0) { // cut bar
			$duration += $offset;
			$offset = 0;
		}

		$style = sprintf('width: %spx; left: %spx;', $duration, $offset);
		$class = 'task ' . $schedule->getStatus();
		$id = 'id_'.$schedule->getScheduleId();

		return sprintf('class="%s" style="%s" id="%s"', $class, $style, $id);
	}

}
