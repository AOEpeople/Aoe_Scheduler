## Changelog

### Version 1.5.0

- **Feature**: #273: Add memory usage column to scheduler list (Thanks, @steverobbins!)
- **Feature**: Added new shell commands `--enableJob` and `--disableJob`
- **Feature**: Added cache prefix check (see [description here](https://github.com/AOEpeople/Aoe_Scheduler/blob/017490e64f40f13e334112307f807d18b773618a/app/code/community/Aoe/Scheduler/etc/system.xml#L177-L185))
- and some other minor bugfixes,...

### Version 1.4.0

- **Feature**: Added bumper-to-bumper feature (status: "repeat") for always tasks
- **Fix**: #117/#199 Fixed duplicate schedules at the time of schedule generation (Thank you, @dorrogeray/@nemphys) 
- **Feature**: #210: Make a difference between missed and skipped jobs (Thank you, @ajpevers) 
- **Feature**: #211: Add relevant titles to scheduler admin pages (Thank you, @steverobbins)
- **Fix**: #213: Fatal Error: Can't use method return value in write context
- **Fix**: #214: Cannot filter List View by "running"
- **Feature**: #189: force user. (Thank you, @robbieaverill)

### Version 1.3.1

- **Fix**: #198: abstract.php not found

### Version 1.3.0

- **Feature**: Added `--force` and `--tryLock` to runNow from cli. This allows you to bypass checking if another instance of that task is already running and start a new one. (Only do this if you know what you're doing...)

### Version 1.2.2

- **Fix**: Died jobs does not get cleaned (#146) 

### Version 1.2.1

- **Fix**: Lock identifiers in scheduler_cron.php might be wrong (Thanks, @Caprico85 and @minimax59)

### Version 1.2.0

- **Even more robust process management**:
  - Now no job should ever disappear without you being able to track what happened. Using `register_shutdown_function()`, 'pcntl_signal()` and the custom php error logs introduced in 1.1 you should be able to detect
     - if a job died because of a PHP fatal error
     - if there was a hidden `exit` or `die` in the job implementation that would have resulted in the scheduler to stop without saving the status
     - if the script stopped because of the memory_limit
     - if the process was killed from outside (using CTRL+C or `kill <pid>`. Please note that `kill -9 <pid>` can not be detected!)

### Version 1.1.2

- **More robust process management**:
  - Prevent all tasks to be cleaned up immediately if `system/cron/mark_as_error_after` was set to 0
  - Check if task is alive before marking it disappeared
  - Add message if job was killed because of `system/cron/max_job_runtime`

### Version 1.1.1

- **Fixed Instructions**: When using `scheduler_cron.sh` (instead of `cron.sh`) you need to configure the default and always job in two seperate lines since we're not doing any ugly launching-processes-in-the-background there.
The instructions were wrong before suggesting that `scheduler_cron.sh` only without any parameters would be a sufficient configuration. But this would lead to the always tasks never being executed.

### Version 1.1.0

- **Error Log**: New configuration options were introduced that allow you to enable a per-job error-log that will be written to ```var/log/cron/<jobCode>_<jobId>.log```. Also you can specifiy the error level (defaults to '-1' which might be a little too verbose for many use cases). This will bypass Magento's `mageCoreErrorHandler` that would hide some errors.
Please note that currently the files in var/log/cron will not be automatically deleted (even if the corresponding job is deleted). Until this feature will be added please use log rotation or other scripts to prevent that directory from growing.
The filename and the prefix can be configured.
- **New states**: Introduced `Aoe_Scheduler_Model_Schedule::STATUS_SKIP_LOCKED` and `Aoe_Scheduler_Model_Schedule::STATUS_SKIP_OTHERJOBRUNNING` for more fine-grained information why a job wasn't run.
Also the grid and timeline view will now show these skipped jobs.
- **Bugfix**: Timeline view didn't render if jobs had been executed via `php scheduler.php --action runNow --code <jobCode>`

### Version 1.0.0

#### What's new in Aoe_Scheduler v1.0.x? (Youtube)

[![](http://img.youtube.com/vi/cbMPIfUjCPs/hqdefault.jpg)](http://www.youtube.com/watch?v=cbMPIfUjCPs)

This is a major release of Aoe_Scheduler. Many things have been added and improved. Please take some time to verify everything is still working as expected after updated.

#### Important: 
- **task_records overlays:** In case you used the experimental task_records branch from GitHub please note that any "database overlays" you might have created will be deleted, since these database overlays are now being stored in a different way. If you don't know what this is about you can safely ignore this note :)
- **Manage cron groups via environment variables:** Previously it was possible to configure cron groups by defining environment variables with blacklists and whitelists. While this worked it wasn't a very elegant solution and had some issues. With the this new release of Aoe_Scheduler the processing of cron groups was rewritten from scratch and **controlling the groups via environment variables was removed**. If you were using this feature please go back and revisit your cron configuration. Please checkout the new [instructions interface](doc/instructions.md), the [cron configuration](doc/cron-configuration.md) page and the information on [cron groups](doc/cron-groups.md) in the documentation. 

#### Changes: 
- **Disabling jobs:** By default Magento doesn't support disabling individual cron jobs. This feature was introduced in an early version of Aoe_Scheduler and the information on what jobs are disabled was stored in a comma-separated list in the system configuration. With the now database overlays this way of storing the status was removed and migrated to the new database overleys instead. While there's a data migration script in place that should take care of the conversion I suggest double checking the list of crons to make sure jobs you disabled before are still disabled after updating.

#### New features and improvements

- **Major code cleanup**: While previous versions of Aoe_Scheduler tried to stick as close of possible to the native implementation in `Mage_Cron` this has changed now. Aoe_Scheduler introduces a separate domain model that structures the code a lot better and allows adding new features in a much cleaner way. At the same time everything is still compatible to the natvie `Mage_cron` module. If you decide to uninstall Aoe_Schedulers your jobs will continue to be executed by Magento.
- **Clean vocabulary**: In the interface and in the code base there was some confusing on what the different terms actually are. There was 'job', 'schedule', 'task', 'run'. Now there's only 'jobs' and 'schedules'. Check out the [documentation](doc/basics.md) for some more details.
- **Cron groups**: See note above in the 'important' section.
- **scheduler_cron.sh**: In order to implement cron groups and other features in a cleaner way a new script was introduced: `scheduler_cron.sh`. Use this instead of `cron.php` or `cron.sh`.
- **Database overlays**: This is probably the biggest addition to the new Aoe_Scheduler. Behind the scenes Magento was already doing this to some degree, but - as so often - this was not clearly documented and exposed to the developers. Job definitions now can not only live in XML but also be definied in the database. You can **edit existing jobs** (the database records can override XML jobs) e.g. disabled them, add some parameters or change settings like the schedule or you can **create new jobs from scratch via the Magento admin** without writing a single line of XML.  
- **Process management**: Aoe_Scheduler is now keeping track of pids and hostnames of schedules allowing you to actually **check if a schedule is still running** and to **actively kill it** in case you need this job to stop (e.g. before deploying a new build,...)  
- **Documentation**: Still work in progress, but we're getting there... :)
- **Tests**: Some integration [tests](doc/tests.md) are added.
- **Schedule meta-data**: Aoe_Scheduler now records who triggered a schedule and why (Aoe_Scheduler_Model_Schedule::REASON_*) 
- **Travis CI**: Some basic build and CS checks: https://travis-ci.org/AOEpeople/Aoe_Scheduler
- **Message buffer**: Contribution from [Mike Weerdenburg](https://github.com/weerdenburg). Thank you!
- **Job descriptions and titles**: Contribution from [Matthias Zeis](https://github.com/mzeis), Thank you!
- **Bugfixes and other contributions**: Thanks, [Robin Fritze](https://github.com/robinfritze), [Nicolai Essig](https://github.com/thakilla), [Chernjie](https://github.com/chernjie), [Jason Evans](https://github.com/jasonevans1)! (and maybe some other! I'm sorry if I missed you here. Let me know and I'll add you to the list!)
