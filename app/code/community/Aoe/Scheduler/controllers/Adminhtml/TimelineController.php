<?php
/**
 * Timeline controller
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Adminhtml_TimelineController extends Aoe_Scheduler_Controller_AbstractController
{

    /**
     * Acl checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/aoe_scheduler/aoe_scheduler_timeline');
    }

    public function dataAction()
    {
        $jobs = array();

        $collection = Mage::getModel('cron/schedule')->getCollection(); /* @var Mage_Cron_Model_Resource_Schedule_Collection $collection */

        $minDate = null;
        $maxDate = null;

        foreach ($collection as $schedule) { /* @var Aoe_Scheduler_Model_Schedule $schedule */

            $startTime = $schedule->getStarttime();
            if (empty($startTime)) {
                continue;
            }

            $code = $schedule->getJobCode();
            $jobs[$code]['code'] = $code;
            $jobs[$code]['schedules'][] = array(
                'schedule_id' => $schedule->getId(),
                'status' => $schedule->getStatus(),
                'duration' => $schedule->getDuration(),
                'start_time' => strtotime($startTime)
            );

            $minDate = is_null($minDate) ? $startTime : min($minDate, $startTime);
            $maxDate = is_null($maxDate) ? $startTime : max($maxDate, $startTime);

        }
        $helper = Mage::helper('aoe_scheduler'); /* @var $helper Aoe_Scheduler_Helper_Data */

        $hours = array();
        for ($i=$helper->hourFloor(strtotime($minDate)); $i<=$helper->hourFloor(strtotime($maxDate)); $i+=60*60) {
            $hours[] = array(
                'label' => $helper->decorateTime($i, false, 'Y-m-d H:i')
            );
        }

        $data = array(
            'jobs' => array_values($jobs),
            'hours' => $hours,
            'now' => time(),
            'start_time' => $helper->hourFloor(strtotime($minDate))
        );

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($data));
    }
}
