
## Watchdog

The watchdog is a separate lightweight process that does two things:

- check what jobs are still running (by checking the processes recorded pid)
- process "kill requests"

Both of these tasks also happen during the regular cron processing, but if you have long running tasks and may want to kill them (or if the get "lost" from time to time, for whatever reason) you might want to have a separate watchdog in place since there's a possibility that your main cron process is busy running another task and will not be executed in a very long time.

Think about running an import job that might run for an hour or longer but at some point you decide to kill it since a new file is coming in and you need to import this one instead. 

If you "kill" the job and this can't be done right away (e.g. because your Magento backend runs on a different server) a "kill request" will be recorded and the watchdog makes sure it will be processed as soon as possible. 

Depending on your requirements you might not need a watchdog process at all or you can configure it to run every 5 or 10 minutes.

This is how to execute the watchdog

```
cd <Webroot>/shell && php scheduler.php --action watchdog
```
