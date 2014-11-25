# Frequently asked questions

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