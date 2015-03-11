## Changelog

### Version 1.0.0

This is a major release or Aoe_Scheduler. Many things have been added and improved. Please take some time to verify everything is still working as expected after updated.

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
