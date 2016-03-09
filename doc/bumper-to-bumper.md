## Bumper-to-bumper (REPEAT)

In some cases jobs are being used to process items from a queue. In order to accelerate processing items a schedule can now return a new status code "repeat" (`Aoe_Scheduler_Model_Schedule::STATUS_REPEAT`).
This will result Aoe_Scheduler in running this same job again right after the first one has finished. In order to protect endless loops currently only 10 repetitions are allowed.

The advantage of repeating schedule like this (as opposed to just continuing to run the code within the callback) are that if something goes wrong only that repetition fails instead 
of a long-running task. In addition to that an individual task run would still be light-weight and well under the configured max duration (`mark_as_error_after`).
Finally (currently not implemented), when every schedule is run in a separate process you'll not run into memory leak issues and have Aoe_Scheduler take care of 
coordinating running the processes for you.
 
### Example code

See [app/code/community/Aoe/Scheduler/Model/Task/QueueProcessorExample.php](../app/code/community/Aoe/Scheduler/Model/Task/QueueProcessorExample.php)
