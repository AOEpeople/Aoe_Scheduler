## Scheduler job groups
Aoe_Scheduler is capable of splitting your cron jobs into different groups so you can run them in parallel or distribute them on multiple servers.
Running multiple scheduler jobs in parallel is quite handy when you have some jobs which take a long time to run, and are not crucial for shop to run properly. An example could be a job which generates some kind of reports. 
Aoe Scheduler comes with whitelist/blacklist feature enabling you to do so.

### How to make it work:
1. Assign a job to a group. Either from `System > Scheduler > Job Configuration > Edit` form, or from xml configuration:
    ```
    <crontab>
        <jobs>
            <job_code>
                <run>
                ....
                </run>
                <groups>reporting,other_group_name</groups>
            </job_code>
        </jobs>
    </crontab>
    ```
    Aoe_Scheduler will merge xml configuration with the one from db.

2. Configure your crontab entry using `--includeGroups` and `--excludeGroups`
You can find examples in `System > Scheduler > Instructions`. See more about [Instructions here](instructions.md)




