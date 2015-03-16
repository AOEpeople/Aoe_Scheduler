
## Schedule status

### Default states (Mage_Cron_Model_Schedule)

* STATUS_PENDING = 'pending';
* STATUS_RUNNING = 'running';
* STATUS_SUCCESS = 'success';
* STATUS_MISSED = 'missed';
* STATUS_ERROR = 'error';

### Additional states (Aoe_Scheduler_Model_Schedule

* STATUS_KILLED = 'killed';
* STATUS_DISAPPEARED = 'gone';
* STATUS_DIDNTDOANYTHING = 'nothing';

TODO: 
- Explain all states
- Convert this into a table?

### How to report a state back?

Only success, error and nothing can be actively reported back. The other states are being determined by the scheduler.

Default state: success

Simple way: Prepend return value with 'ERROR:' or 'NOTHING:' 