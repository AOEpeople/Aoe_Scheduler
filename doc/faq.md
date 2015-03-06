## Frequently asked questions

### Is there a way to run a single job like at the scheduler configuration with selecting the job and start "run now" via URL or something like that?

Please read the answer on why "run now" is a bad idea generally.   
In addition to that it's currently not possible to trigger an individual job via an url. While in most setups it is possible to hit cron.php via the webserver this is also not recommended.

If you need to address a specific job dynamically please use the Magento webservice API that gives you access to scheduling jobs. But even that is not a single url you can hit. 

Adding this feature to Aoe_Scheduler is not a big deal, but in order to make this a safe operation we'd have to think about signed urls or adding another form of authentication (e.g. through special headers or additional POST data).

Check out the documentation (currently here: http://www.fabrizio-branca.de/magento-cron-scheduler.html) on how to use the webservice api.

### Why is "run now" a bad idea and what should I use instead?

"Run now" should only be used in a cli context. That's why never versions Aoe_Scheduler disable the use of runNow from the web service API and the Magento admin by default. Instead of "run now" you should use "schedule now" which will result in a task to be scheduled and then being picked from the regular cron and processed in the correct cli context. 
Running a job in the context of the webserver is generally a bad idea since you could run into memory limit out timeout issues. Cron tasks are designed to run from cli and will usually run longer than 30 sec. Increasing your webservers memory limit and maximum execution time is also not a good idea.

### After removing module XYZ I'm seeing an error. What can I do?

Most likely you didn't remove all files that belonged this module and/or didn't clean the cache. The error happens if the job is still configured in config xml (config/crontab/jobs) and scheduler tries to excute it by calling the configured model class that doesn't exist anymore. Please make sure that the module is removed cleanly.   
Starting from Aoe_Scheduler version 0.5 (still under development and not merged to master) these situations are handled a lot better without scheduler. Let me know if you're still experiencing this problem with Aoe_Scheduler >= 0.5.

### I'm getting 'Too late for the schedule' errors.

This happens when the scheduler finds pending jobs that were supposed to be scheduled longer than the time configured in 'system/cron/schedule_lifetime'. By default this value is set to 15 minutes.

There are two different problems the may result in you seeing this error:

1. Cron isn't configured to run often enough:
If you don't trigger cron often enough then the tasks start piling up and most likely tasks will be too late for schedule at the point when they're being executed by the scheduler. Instead of increasing the scheduler_lifetime settings you should increase the frequency cron is being called to `*/5 * * * *` (every 5 minutes) or even `* * * *  *` (every minute).

2. You have long running cron jobs that will block the execution of other jobs:
In case you're importing data, indexing products, generating reports or doing other long-running jobs via cron (which is generally a good idea) other jobs will not be run in parallel (unless you're running `cron.php` instead of `cron.sh`). This will result in these jobs not being executed. Find out which jobs are preventing others from running (by looking at the timeline view) and run them in a different *cron group*. ([look at this](http://www.slideshare.net/aoemedia/magento-imagine-2013fabriziobranca/38) for more information and check the features added to version >0.5.0 for an easier way to configure and manage cron groups.

### My hoster doesn't allow me to run cron more than x times per hour or at all.

Then your hoster might not be a good choice for Magento :) Although possible, please don't start triggering `cron.php` via HTTP through a third-party cron service.

### Should I use `cron.sh` or `cron.php`?

`cron.sh`!

(TODO: Add more information here)

Even better: `scheduler_cron.sh` from the Aoe_Scheduler module (not merged to master yet). TODO: Add documentation

### What's the difference between an *always* job and a normal one?

...

### How does this work if I'm running multiple webservers?

This is not so much a question about Aoe_Scheduler, but an issue with Magento's cron in general. `cron.sh` does check if there's already another process running, but can only check the current server. Usually you want to avoid running multiple cron.sh in parallel since you could run into race conditions between tasks being executed on different servers. If you have full control over all your servers just pick one and configure cron there and skip this on all the other servers. If you're running Magento on an auto-scaling environment where there's no "master" instance you could dynamically make an instance the "master" instance by having the instance compare it's hostname to a sorted list of all hostnames in that auto-scaling group and only run if it's the first one. Here's an example of how I'm doing this on Aws OpsWorks: https://github.com/fbrnc/opsworks-cookbooks/blob/200a70b07b59e1b5cbed1c612bc064c04a7c0025/magentostack/recipes/configure_magento.rb#L68-L82

### What's the difference between `runNow` and `scheduleNow`?

`scheduleNow` will create a new jobs and uses the current timestamp for the scheduled_at field. The next cron run will pick this task up and execute it exactly like if it was scheduled by the Magento Scheduler in the first place.
`runNow` instead will go ahead and execute the cron task right away in the same request or script it has been called. While this is very convenient while developing it comes with a number of problems:

- `runNow` might not be called in the correct context. Normally a cron task is being executed in the 'global' scope, while runNow will execute that job in the context of adminhtml when triggered wie the Magento backend or even in the scope of a frontend store when being executed through the webservice api. The only safe way to use 'runNow' is when it's triggered via the cli script it comes with.
- In many cases cron tasks are meant to be background processes that can potentially run for a long time or consume a lot of memory. Running a task via your webserver in the context of a web request will could result in failing because a timeout or a memory limit is reached.
- In many cases cron tasks are not designed to run in parallel (race conditions,...). Cron will take care of running jobs sequentially (unless you use the cron group feature and you know what you're doing). Running the job directly doesn't offer this protection.
- Ideally your webserver user is the same user that also executes cron. If that's not the case you might run into problem with files being generated in cron tasks not having sufficient permissions for the next run from a different environment.

Because of the reasons mentioned above newer versions of Aoe_Scheduler ship with the "runNow" feature being disabled by default. In case you know what you're doing you're welcome to enable this feature in the configuration.

### I've uninstalled a module that introduced cron jobs and now I'm gettings error messages. What's going on?

TODO...

### Will this work in CE and EE?

TODO...

### Will this work in version x?

TODO...

### Will this work in version Windows?