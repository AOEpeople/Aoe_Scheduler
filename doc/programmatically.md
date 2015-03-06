## Schedule a job programmatically

```
$schedule = Mage::getModel('cron/schedule'); /* @var $schedule Aoe_Scheduler_Model_Schedule */
$schedule->setScheduledReason(Aoe_Scheduler_Model_Schedule::REASON_SCHEDULENOW_CLI);
$schedule->setJobCode($code);
$schedule->schedule();
$schedule->save();
```

## Run a job programmatically

```
$schedule = Mage::getModel('cron/schedule'); /* @var $schedule Aoe_Scheduler_Model_Schedule */
$schedule->setJobCode($code);
$schedule->setScheduledReason(Aoe_Scheduler_Model_Schedule::REASON_RUNNOW_CLI);
$schedule->runNow(false);
$schedule->save();
```