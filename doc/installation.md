## Installation

**NOTE:** Running Aoe_Scheduler requires you to also setup Magento's cron. If you haven't already done this please also check out [this section on how to configure cron](cron-configuration.md)

For general information on how to install a Magento module using composer/modman, by copying it of via Magento connect please read this post: 
[http://fbrnc.net/blog/2014/11/how-to-install-a-magento-module](http://fbrnc.net/blog/2014/11/how-to-install-a-magento-module)

### Composer

Aoe_Scheduler is registered on [Packagist.org](https://packagist.org/packages/aoepeople/aoe_scheduler) so you don't have to add any additional repositories to you composer.json file:

Install Aoe_Scheduler using the [Hackathon Composer Installer](https://github.com/magento-hackathon/magento-composer-installer) - Read [Option 4.a in this blog post](http://fbrnc.net/blog/2014/11/how-to-install-a-magento-module)

```json
{
    "minimum-stability": "dev",
    "require": {
        "aoepeople/aoe_scheduler": "1.0.*"
    }
}
```

If you want to use the AOEPeople Composer installer instead (and manually run modman afterwards) use this snippet (Read [Option 4.b in this blog post](http://fbrnc.net/blog/2014/11/how-to-install-a-magento-module)):

```json
{
    "minimum-stability": "dev",
    "require": {
        "aoepeople/composer-installers": "dev-master",
        "aoepeople/aoe_scheduler": "1.0.*"
    }
}
```

### Download and extract

Of course you can also [download the zip or tar.gz file](https://github.com/AOEpeople/Aoe_Scheduler/releases) and extract it into your project webroot. While this is **not recommended (especially not on a production environment!)** this might be the easiest way to test Aoe_Scheduler if your not familiar with Composer and modman and don't feel like exploring it (you should! :) 

