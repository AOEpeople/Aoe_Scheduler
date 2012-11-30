<?php

/**
 * Timeline block
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Block_Adminhtml_Timeline extends Mage_Adminhtml_Block_Widget_Container {

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
	protected function _construct() {
		$this->_headerText = Mage::helper('aoe_scheduler')->__('Scheduler Timeline');
		$this->loadSchedules();
		parent::_construct();
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

		$minDate = null; $maxDate = null;

		foreach ($collection as $schedule) { /* @var $schedule Aoe_Scheduler_Model_Schedule */
			$startTime = $schedule->getStarttime();
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
	public function getTimelinePanelWidth() {
		return ($this->endtime - $this->starttime) / $this->zoom;
	}



	/**
	 * Get "now" line
	 *
	 * @return float
	 */
	public function getNowline() {
		return (time() - $this->starttime) / $this->zoom;
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
	 * Get attributes for div representing a gantt element
	 *
	 * @param Aoe_Scheduler_Model_Schedule $schedule
	 * @return string
	 */
	public function getGanttDivAttributes(Aoe_Scheduler_Model_Schedule $schedule) {

		if ($schedule->getStatus() == Mage_Cron_Model_Schedule::STATUS_RUNNING) {
			$duration = time() - strtotime($schedule->getExecutedAt());
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

		return sprintf('class="task %s" id="id_%s" style="width: %spx; left: %spx;"',
			$schedule->getStatus(),
			$schedule->getScheduleId(),
			$duration,
			$offset
		);
	}

}
