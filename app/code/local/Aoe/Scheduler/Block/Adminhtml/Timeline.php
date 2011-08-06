<?php
class Aoe_Scheduler_Block_Adminhtml_Timeline extends Mage_Adminhtml_Block_Template {

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

	protected function _construct() {
		parent::_construct();

		$this->loadSchedules();

		$this->starttime = mktime(date('H', strtotime($this->minDate)), 0, 0);
		$this->endtime = mktime(date('H', strtotime($this->maxDate))+1, 0, 0);

		$this->nowLine = (time() - $this->starttime) / $this->zoom;
	}

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

	public function getTimelinePanelWidth() {
		return ($this->endtime - $this->starttime) / $this->zoom;
	}

	public function getAvailableJobCodes() {
		return array_keys($this->schedules);
	}

	public function getSchedulesForCode($code) {
		return $this->schedules[$code];
	}

	public function getStarttime() {
		return $this->starttime;
	}

	public function getEndtime() {
		return $this->endtime;
	}

	public function getNowline() {
		return $this->nowLine;
	}

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

		$title = $schedule->getId();
		$style = sprintf('width: %spx; left: %spx;', $duration, $offset);
		$class = 'task ' . $schedule->getStatus();
		$id = 'id_'.$schedule->getScheduleId();

		return sprintf('title="%s" class="%s" style="%s" id="%s"', $title, $class, $style, $id);
	}

}
