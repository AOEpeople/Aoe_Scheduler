<?php
/**
 * Cron controller
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Adminhtml_JobController extends Aoe_Scheduler_Controller_AbstractController
{
    /**
     * Mass action: disable
     *
     * @return void
     */
    public function disableAction()
    {
        $codes = $this->getMassActionCodes();
        foreach ($codes as $code) {
            /** @var Aoe_Scheduler_Model_Job $job */
            $job = Mage::getModel('aoe_scheduler/job')->load($code);
            if ($job->getJobCode() && $job->getIsActive()) {
                $job->setIsActive(false)->save();
                $this->_getSession()->addSuccess($this->__('Disabled "%s"', $code));

                /* @var Aoe_Scheduler_Model_ScheduleManager $scheduleManager */
                $scheduleManager = Mage::getModel('aoe_scheduler/scheduleManager');
                $scheduleManager->flushSchedules($job->getJobCode());
                $this->_getSession()->addNotice($this->__('Pending schedules for "%s" have been flushed.', $job->getJobCode()));
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * Mass action: enable
     *
     * @return void
     */
    public function enableAction()
    {
        $codes = $this->getMassActionCodes();
        foreach ($codes as $code) {
            /** @var Aoe_Scheduler_Model_Job $job */
            $job = Mage::getModel('aoe_scheduler/job')->load($code);
            if ($job->getJobCode() && !$job->getIsActive()) {
                $job->setIsActive(true)->save();
                $this->_getSession()->addSuccess($this->__('Enabled "%s"', $code));

                /* @var Aoe_Scheduler_Model_ScheduleManager $scheduleManager */
                $scheduleManager = Mage::getModel('aoe_scheduler/scheduleManager');
                $scheduleManager->generateSchedulesForJob($job);
                $this->_getSession()->addNotice($this->__('Job "%s" has been scheduled.', $job->getJobCode()));
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * Mass action: schedule now
     *
     * @return void
     */
    public function scheduleNowAction()
    {
        $codes = $this->getMassActionCodes();
        foreach ($codes as $key) {
            Mage::getModel('cron/schedule')
                ->setJobCode($key)
                ->setScheduledReason(Aoe_Scheduler_Model_Schedule::REASON_SCHEDULENOW_WEB)
                ->schedule()
                ->save();

            $this->_getSession()->addSuccess($this->__('Job "%s" has been scheduled.', $key));
        }
        $this->_redirect('*/*/index');
    }

    /**
     * Mass action: run now
     *
     * @return void
     */
    public function runNowAction()
    {
        if (!Mage::getStoreConfig('system/cron/enableRunNow')) {
            Mage::throwException("'Run now' disabled by configuration (system/cron/enableRunNow)");
        }
        $codes = $this->getMassActionCodes();
        foreach ($codes as $key) {
            $schedule = Mage::getModel('cron/schedule')
                ->setJobCode($key)
                ->setScheduledReason(Aoe_Scheduler_Model_Schedule::REASON_RUNNOW_WEB)
                ->runNow(false)// without trying to lock the job
                ->save();

            $messages = $schedule->getMessages();

            if (in_array($schedule->getStatus(), array(Aoe_Scheduler_Model_Schedule::STATUS_SUCCESS, Aoe_Scheduler_Model_Schedule::STATUS_DIDNTDOANYTHING))) {
                $this->_getSession()->addSuccess($this->__('Ran "%s" (Duration: %s sec)', $key, intval($schedule->getDuration())));
                if ($messages) {
                    $this->_getSession()->addSuccess($this->__('"%s" messages:<pre>%s</pre>', $key, $messages));
                }
            } else {
                $this->_getSession()->addError($this->__('Error while running "%s"', $key));
                if ($messages) {
                    $this->_getSession()->addError($this->__('"%s" messages:<pre>%s</pre>', $key, $messages));
                }
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * Init job instance and set it to registry
     *
     * @return Aoe_Scheduler_Model_Job
     */
    protected function _initJob()
    {
        $jobCode = $this->getRequest()->getParam('job_code', null);
        $job = Mage::getModel('aoe_scheduler/job')->load($jobCode);
        Mage::register('current_job_instance', $job);
        return $job;
    }

    protected function getMassActionCodes($key = 'codes')
    {
        $codes = $this->getRequest()->getParam($key);
        if (!is_array($codes)) {
            return array();
        }
        $allowedCodes = Mage::getSingleton('aoe_scheduler/job')->getResource()->getJobCodes();
        $codes = array_intersect(array_unique(array_filter(array_map('trim', $codes))), $allowedCodes);
        return $codes;
    }

    /**
     * New cron (forward to edit action)
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * Edit cron action
     */
    public function editAction()
    {
        $this->_initJob();
        $this->loadLayout();
        $this->renderLayout();
    }

    protected function _filterPostData($data)
    {
        return $data;
    }

    protected function _validatePostData($data)
    {
        try {
            /* @var Aoe_Scheduler_Helper_Data $helper */
            $helper = Mage::helper('aoe_scheduler');
            $helper->getCallBack($data['run_model']);
            if (!empty($data['schedule_cron_expr'])) {
                if (!$helper->validateCronExpression($data['schedule_cron_expr'])) {
                    Mage::throwException("Invalid cron expression");
                }
            }
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            return false;
        }
        // TODO: implement!
        return true;
    }

    /**
     * Save action
     *
     */
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            $data = $this->_filterPostData($data);
            $job = $this->_initJob();
            $job->addData($data);
            //validating
            if (!$this->_validatePostData($data)) {
                $this->_redirect('*/*/edit', array('job_code' => $job->getJobCode(), '_current' => true));
                return;
            }

            try {
                // save the data
                $job->save();

                // display success message
                $this->_getSession()->addSuccess(
                    Mage::helper('aoe_scheduler')->__('The job has been saved.')
                );
                // clear previously saved data from session
                $this->_getSession()->setFormData(false);
                // check if 'Save and Continue'
                if ($this->getRequest()->getParam('back', false)) {
                    $this->_redirect('*/*/edit', array('job_code' => $job->getJobCode(), '_current' => true));
                    return;
                }

                /* @var $scheduleManager Aoe_Scheduler_Model_ScheduleManager */
                $scheduleManager = Mage::getModel('aoe_scheduler/scheduleManager');
                $scheduleManager->flushSchedules($job->getJobCode());
                $scheduleManager->generateSchedulesForJob($job);
                $this->_getSession()->addNotice($this->__('Pending schedules for "%s" have been flushed.', $job->getJobCode()));
                $this->_getSession()->addNotice($this->__('Job "%s" has been scheduled.', $job->getJobCode()));

                // go to grid
                $this->_redirect('*/*');
                return;
            } catch (Mage_Core_Exception $e) {
                Mage::logException($e);
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_getSession()->addError($this->__('An error occurred during saving a job: %s', $e->getMessage()));
            }

            $this->_getSession()->setFormData($data);
            $this->_redirect('*/*/edit', array('job_code' => $this->getRequest()->getParam('job_code')));
            return;
        }

        $this->_redirect('*/*/', array('_current' => true));
    }

    /**
     * Delete Action
     *
     */
    public function deleteAction()
    {
        $job = $this->_initJob();
        try {
            $job->delete();
            $this->_getSession()->addSuccess($this->__('The job has been deleted.'));

            /* @var Aoe_Scheduler_Model_ScheduleManager $scheduleManager */
            $scheduleManager = Mage::getModel('aoe_scheduler/scheduleManager');
            $scheduleManager->flushSchedules($job->getJobCode());
            $this->_getSession()->addNotice($this->__('Pending schedules for "%s" have been flushed.', $job->getJobCode()));
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        $this->_redirect('*/*/');
        return;
    }

    /**
     * ACL checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/aoe_scheduler/aoe_scheduler_cron');
    }
}
