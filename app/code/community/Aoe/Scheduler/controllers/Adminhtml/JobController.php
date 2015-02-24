<?php

require_once Mage::getModuleDir('controllers', 'Aoe_Scheduler') . '/Adminhtml/AbstractController.php';

/**
 * Cron controller
 *
 * @author Fabrizio Branca
 */
class Aoe_Scheduler_Adminhtml_JobController extends Aoe_Scheduler_Adminhtml_AbstractController
{

    /**
     * Mass action: disable
     *
     * @return void
     */
    public function disableAction()
    {
        $codes = $this->getRequest()->getParam('codes');
        $disabledCrons = Mage::helper('aoe_scheduler')->trimExplode(',', Mage::getStoreConfig('system/cron/disabled_crons'), true);
        foreach ($codes as $code) {
            if (!in_array($code, $disabledCrons)) {
                $disabledCrons[] = $code;
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Disabled "%s"', $code));
            }
        }
        Mage::getModel('core/config')->saveConfig('system/cron/disabled_crons/', implode(',', $disabledCrons));
        Mage::app()->getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array(Mage_Core_Model_Config::CACHE_TAG));
        $this->_redirect('*/*/index');
    }

    /**
     * Mass action: enable
     *
     * @return void
     */
    public function enableAction()
    {
        $codes = $this->getRequest()->getParam('codes');
        $disabledCrons = Mage::helper('aoe_scheduler')->trimExplode(',', Mage::getStoreConfig('system/cron/disabled_crons'), true);
        foreach ($codes as $key => $code) {
            if (in_array($code, $disabledCrons)) {
                unset($disabledCrons[array_search($code, $disabledCrons)]);
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Enabled "%s"', $code));
            }
        }
        Mage::getModel('core/config')->saveConfig('system/cron/disabled_crons/', implode(',', $disabledCrons));
        Mage::app()->getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array(Mage_Core_Model_Config::CACHE_TAG));
        $this->_redirect('*/*/index');
    }

    /**
     * Mass action: schedule now
     *
     * @return void
     */
    public function scheduleNowAction()
    {
        $codes = $this->getRequest()->getParam('codes');
        if (is_array($codes)) {
            foreach ($codes as $key) {
                Mage::getModel('cron/schedule')/* @var Aoe_Scheduler_Model_Schedule */
                    ->setJobCode($key)
                    ->setScheduledReason(Aoe_Scheduler_Model_Schedule::REASON_SCHEDULENOW_WEB)
                    ->schedule()
                    ->save();
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Scheduled "%s"', $key));
            }
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
        $codes = $this->getRequest()->getParam('codes');
        if (is_array($codes)) {
            foreach ($codes as $key) {
                $schedule = Mage::getModel('cron/schedule')/* @var $schedule Aoe_Scheduler_Model_Schedule */
                    ->setJobCode($key)
                    ->setScheduledReason(Aoe_Scheduler_Model_Schedule::REASON_RUNNOW_WEB)
                    ->runNow(false) // without trying to lock the job
                    ->save();

                $messages = $schedule->getMessages();

                if ($schedule->getStatus() == Mage_Cron_Model_Schedule::STATUS_SUCCESS) {
                    Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Ran "%s" (Duration: %s sec)', $key, intval($schedule->getDuration())));
                    if ($messages) {
                        Mage::getSingleton('adminhtml/session')->addSuccess($this->__('"%s" messages:<pre>%s</pre>', $key, $messages));
                    }
                } else {
                    Mage::getSingleton('adminhtml/session')->addError($this->__('Error while running "%s"', $key));
                    if ($messages) {
                        Mage::getSingleton('adminhtml/session')->addError($this->__('"%s" messages:<pre>%s</pre>', $key, $messages));
                    }
                }

            }
        }
        $this->_redirect('*/*/index');
    }





    /**
     * Init job instance and set it to registry
     *
     * @return Aoe_Scheduler_Model_Job_Db
     */
    protected function _initJob()
    {
        $jobCode = $this->getRequest()->getParam('job_code', null);
        $job = Mage::getModel('aoe_scheduler/job_db'); /* @var $job Aoe_Scheduler_Model_Job_Db */
        if ($jobCode) {
            $job->load($jobCode);
            if (!$job->getJobCode()) {
                $job->setJobCode($jobCode);
                $parentJob = $job->getParentJob();
                if ($parentJob) {
                    $job->copyFrom($parentJob);
                }
            }
        }
        Mage::register('current_job_instance', $job);
        return $job;
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
        $job = $this->_initJob();
        if (!$job) {
            $this->_redirect('*/*/');
            return;
        }
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
            $helper = Mage::helper('aoe_scheduler'); /* @var $helper Aoe_Scheduler_Helper_Data */
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
            if (!$job) {
                $this->_redirect('*/*/');
                return;
            }
            Mage::log($data);
            $job->setData($data);
            //validating
            if (!$this->_validatePostData($data)) {
                $this->_redirect('*/*/edit', array('job_code' => $job->getId(), '_current' => true));
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
                    $this->_redirect('*/*/edit', array('job_code' => $job->getId(), '_current' => true));
                    return;
                }

                // flush and generate future schedules
                $scheduleManager = Mage::getModel('aoe_scheduler/scheduleManager'); /* @var $scheduleManager Aoe_Scheduler_Model_ScheduleManager */
                $scheduleManager->flushSchedules($job->getJobCode());
                $scheduleManager->generateSchedules();
                $this->_getSession()->addNotice($this->__('Future pending jobs have been flushed and regenerated'));

                // go to grid
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
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
        if ($job) {
            try {
                $job->delete();
                $this->_getSession()->addSuccess(
                    Mage::helper('aoe_scheduler')->__('The job has been deleted.')
                );

                // flush and generate future schedules
                $scheduleManager = Mage::getModel('aoe_scheduler/scheduleManager'); /* @var $scheduleManager Aoe_Scheduler_Model_ScheduleManager */
                $scheduleManager->flushSchedules($job->getJobCode());
                $scheduleManager->generateSchedules();
                $this->_getSession()->addNotice($this->__('Future pending jobs have been flushed and regenerated'));

            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/');
        return;
    }







    /**
     * Acl checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/aoe_scheduler/aoe_scheduler_cron');
    }

}

