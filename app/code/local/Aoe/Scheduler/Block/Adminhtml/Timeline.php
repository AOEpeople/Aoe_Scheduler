<?php
class Aoe_Scheduler_Block_Adminhtml_Timeline extends Mage_Adminhtml_Block_Template {

	/**
	 * @var int amount of hours to be displayed
	 */
	protected $hours = 25;

	/**
	 * @var int amount of seconds per pixel
	 */
	protected $zoom = 15;

	protected $starttime;

	protected $endtime;

	protected $currentTimeStamp;

	protected $timelinePanelWidth;

	protected $nowLine;

	protected function _construct() {
		parent::_construct();

		$this->currentTimeStamp = time();

		$pixelsPerMinute = 60 / $this->zoom;
		$pixelsPerHour = 60 * $pixelsPerMinute;

		$this->timelinePanelWidth = $pixelsPerHour * $this->hours;

		$this->starttime = mktime(date('H', $this->currentTimeStamp)-($this->hours-1), 0, 0);
		$this->endtime = mktime(date('H', $this->currentTimeStamp)+1, 0, 0);

		$this->nowLine = ($this->currentTimeStamp - $this->starttime) / $this->zoom;
	}

	public function getAvailableConfigurations() {
		return Mage::getModel('aoe_scheduler/collection_crons');
	}

	public function getSchedulesForCode($code) {
		$collection = Mage::getModel('cron/schedule')->getCollection(); /* @var $collection Mage_Cron_Model_Mysql4_Schedule_Collection */
		$collection->addFieldToFilter('job_code', $code);
		return $collection;
	}

	public function getTimelinePanelWidth() {
		return $this->timelinePanelWidth;
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

		$taskStarttime = $schedule->getExecutedAt();
		if ($taskStarttime == '0000-00-00 00:00:00') {
			$taskStarttime = $schedule->getScheduledAt();
		}

		$offset = (strtotime($taskStarttime) - $this->starttime) / $this->zoom;

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
