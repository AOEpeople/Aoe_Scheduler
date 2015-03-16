## Create a new job

### Job implementation

A job implementation is a simple callback. Your job class doesn't have to inherit from any specific class or implement any interface. You only need to create a model with a public method. Aoe_Scheduler will pass the current schedule object as a parameter in case you need to interact with it:

```php
class My_Module_Model_Job_Hello
{
    /**
     * Log 'Hello World'
     *
     * @param Aoe_Scheduler_Model_Schedule $schedule
     */
    public function run(/* Aoe_Scheduler_Model_Schedule $schedule */)
    {
		Mage::log('Hello World');
    }
}

```

### Register job in XML

In your modules config.xml file (e.g. `app/code/local/My/Module/etc/config.xml`) add this snippet to let Magento know about the job you've created:

```xml
<config>
	<!-- ... -->
    <crontab>
        <jobs>
            <mymodule_helloworld>
                <schedule>
					<cron_expr>*/5 * * * *</cron_expr>
				</schedule>
                <run>
                    <model>my_module/job_hello::run</model>
                </run>
            </mymodule_helloworld>
        </jobs>
    </crontab>
	<!-- ... -->
</config>
```

**Hint:** instead of hardcoding your schedule in '<schedule><cron_expr>' you can use '<schedule><config_path>' instead pointing to a configuration option that holds your schedule. This configuration option can be made avilable through 'System > Configuration'.
On the other hand, Aoe Scheduler now let's you edit any job and overwrite any hardcoded schedule even if it wasn't pointing to a config path.

TODO: Add complete list of xml tags here, with description (e.g. what's the run>model syntax...)!

TODO: Add examples here of how to interact with Aoe_Scheduler through the $schedule object passed while calling the job.