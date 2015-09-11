## How to integrate Aoe_Scheduler into you deployment process

When deploying a new build to your server some tasks might still be running in the background. Here's a simple workflow to make a clean cut:

```shell
# Disable cron processing (here: using n98-magerun). This will not affect tasks that are currently running
cd $rootDir && n98-magerun.phar config:set system/cron/enable 0

# Flush all future schedules (so that no new schedule get's started)
cd $rootDir/shell && php scheduler --action flushSchedules --mode future

# Actively wait until current schedules finish for up to 1 minute (or longer if required):
cd $rootDir/shell && php scheduler --action wait --timout 60

# Kill all tasks that might still be running
cd $rootDir/shell && php scheduler --action killAll

# Deploy!

# Enable cron processing (here: using n98-magerun)
cd $rootDir && n98-magerun.phar config:set system/cron/enable 1

# New schedules will automatically be generated before the next run...

```