## Events

### Before a schedule run

* `cron_[job_code]_before`
	* array('schedule' => $schedule)
* `cron_before`
	* array('schedule' => $schedule)

### After a schedule run 

These events will always be dispatched, no matter of the resulting status of the schedule

* `cron_[job_code]_after`
	* array('schedule' => $schedule)
* `cron_after`
	* array('schedule' => $schedule)

Events dispatched after successful completion only

* `cron_[job_code]_after_success`
	* array('schedule' => $schedule)
* `cron_after_success`
	* array('schedule' => $schedule)

Events dispatched after the schedule reported it did 'nothing';

* `cron_[job_code]_after_nothing`
	* array('schedule' => $schedule)
* `cron_after_nothing`
	* array('schedule' => $schedule)


Events dispatched after an error has happened

* `cron_[job_code]_after_error`
	* array('schedule' => $schedule)
* `cron_after_error`
	* array('schedule' => $schedule)



### On Excpetions

* cron_[job_code]_exception
	* array('schedule' => $schedule, 'exception' => $e)
* cron_exception
	* array('schedule' => $schedule, 'exception' => $e)