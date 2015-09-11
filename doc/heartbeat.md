## Heartbeat

The heartbeat is a simple job that doesn't do anything except helping you to verify that your Magento instance is configured correctly. 

If everything is ok it will show a notice an all Aoe_Scheduler admin interfaces. If you're not seeing this message please check out the [instructions module](instructions.md) and fund some more information on how to [configure cron](cron-configuration.md) in this manual.

![](images/heartbeat.png)

### Monitoring

Sometimes - for various reasons - Magento's scheduler might stop working. Often there are business critical tasks that need to be run on a regular basis, so there's a need in monitoring the if everything works fine behind the scenes. That's why I added a new dummy job "Heartbeat". By default it runs every 5 minutes. This can be configured or even deactivated completely. This job does nothing but showing that the scheduler is working correctly.

In addition to that there is a new shell command that allows you the check when a task was run successfully the last time (works for all scheduler job - not only for the heartbeat job):

```
> php scheduler.php -action lastRun -code aoescheduler_heartbeat  
1327960203
```

By adding the "-secondsFromNow" parameter you'll get the duration since the last execution:

```
> php scheduler.php -action lastRun -code aoescheduler_heartbeat -secondsFromNow  
35
```

So you can use this to integrate your instance to whatever monitoring you're using. Using the default configuration the last value must not be bigger than 5x60 = 300 seconds. If it is, there's something wrong with your scheduler. 

### Check any job

If you have any other critical job you can check specifically check when this job ran as well:
```
> php scheduler.php -action lastRun -code mymodule_helloworld  
1327960263
```
 
