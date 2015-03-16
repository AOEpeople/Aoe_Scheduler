![Logo](doc/images/Aoe_Scheduler.jpg "AOE Scheduler")

# AOE Scheduler for Magento



## Installation



see http://www.fabrizio-branca.de/magento-cron-scheduler.html

Running multiple scheduler jobs in parallel
-------------------------------------------

*INFO*: Running jobs in parallel like this is outdated. Please check the usage of scheduler_cron.sh below.

Running multiple scheduler jobs in parallel is quite handy when you have some jobs which take a long time to run,
and are not crucial for shop to run properly. En excample could be a job which generates some kind of reports.
Magento cron.sh script detects if there is some job already running by parsing output of "ps" command.
If not, it executes cron.php file.
So to run multiple schedulers in parallel we have to use different file name than cron.php.
Additionally we want to run only few jobs with second cron task, and exclude them from the original scheduler job.
Aoe Scheduler comes with whitelist/blacklist feature enabling you to do so.

1. symlink cron.php to cron2.php
2. add crontab configuration (crontab -e) like this:

```
* * * * * /usr/bin/env SCHEDULER_BLACKLIST='job_key1,job_key2' /bin/sh  /home/webapps/htdocs/cron.sh
* * * * * /usr/bin/env SCHEDULER_WHITELIST='job_key1,job_key2' /bin/sh  /home/webapps/htdocs/cron.sh cron2.php
```

cron.sh script takes name of the php script to run as a parameter (by default it is cron.php).
This way the first cron job will execute all scheduler jobs except job_key1 and job_key2, and the second one will execute only them.


scheduler_cron.sh vs cron.sh
----------------------------
Aoe_Scheduler introduces a new shell script acting as an endpoint to execute cron. 

This new endpoint supports including and excluding groups of crons and manages the parallel execution of cron groups.

scheduler_cron.sh then will call shell/scheduler.php --action cron with the correct mode and options parameters instead of running cron.php
Managing multiple configuration this way is much cleaner and easier to understand.

scheduler_cron.sh or shell/scheduler.php --action cron will not do any shell_exec to run the different modes in subtasks. It's up to you to configure
multiple OS level crontasks to trigger these individually.

TODO: Add more information and example code.

Parameters: 
* `--mode [always|default]`
* `--excludeJobs [comma-separated list of job names]` (deprecated!)
* `--includeJobs [comma-separated list of job names]` (deprecated!)
* `--excludeGroups [comma-separated list of cron group names]` 
* `--includeGroups [comma-separated list of cron group names]`

How to define a cron group
--------------------------

### Via XML

```
<my_cron_job>
    <schedule><config_path>...</config_path></schedule>
    <run><model>...</model></run>
    <groups>my_group</groups>
</my_cron_job>
```

### Or in a the group field of a record 

TODO: add screenshot here.

Configuring crontask
--------------------

Please remember to add the following configuration to the webserver user's crontab in order to avoid file permission issues with files that are being generated or need to be accessed from different contexts!

### Split always and default:

```
* * * * * /bin/bash /var/www/magento/current/htdocs/scheduler_cron.sh --mode always
* * * * * /bin/bash /var/www/magento/current/htdocs/scheduler_cron.sh --mode default
```

### Prepend check for maintenance.flag:

```
* * * * * ! test -e /var/www/magento/current/htdocs/maintenance.flag && ...
```

### Configure multiple cron groups

This way you can distribute the jobs on different servers. Also this allows you to control which jobs can run in parallel and which don't. Within a cron group only one job will run at a time, while jobs from multiple cron groups might and will run at the same time 

```
# Always tasks
* * * * * /bin/bash /var/www/magento/current/htdocs/scheduler_cron.sh --mode always --includeGroups my_queue_jobs
* * * * * /bin/bash /var/www/magento/current/htdocs/scheduler_cron.sh --mode always --excludeGroups my_queue_jobs

# Default tasks
* * * * * /bin/bash /var/www/magento/current/htdocs/scheduler_cron.sh --mode default --includeGroups groupA,groupB
* * * * * /bin/bash /var/www/magento/current/htdocs/scheduler_cron.sh --mode default --includeGroups groupC
* * * * * /bin/bash /var/www/magento/current/htdocs/scheduler_cron.sh --mode default --excludeGroups groupA,groupB,groupC
```

If splitting jobs across multiple groups please double check you have every job covered in one of the includeGroups or excludeGroups list.

Watchdog
--------

Please configure an additional cron for the watchdog. This will ensure that locked up jobs are being cleaned up even if every single cron process is locked.

```
*/10 * * * * /usr/bin/php /var/www/magento/current/htdocs/shell/scheduler.php --action watchdog
```