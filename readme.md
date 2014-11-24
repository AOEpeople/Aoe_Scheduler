AOE Scheduler for Magento
=========================
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

TODO: Add more information and example code.