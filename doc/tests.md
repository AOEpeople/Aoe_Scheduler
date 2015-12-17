## Tests

### PHPUnit

I recommend using the PHPUnit phar file. You can download version 4 like this
```
wget https://phar.phpunit.de/phpunit-4.8.21.phar
```

Please note that Magento's autoloader is very picky in since PHPUnit does some `class_exists` to detect if other packages 
(like PHP_Invoker) are installed this will fail if those packages are not installed. Instead of dealing with this problem
the easiest solution is to have all those optional packages in place. Using the phar file is a great solution to this
because everything is bundled inside that phar.

### Plain PHPUnit

The testcase do NOT rely on EcomDev_PHPUnit anymore since EcomDev_PHPUnit was doing way too much "magic" in the background.
The showstopper definitly was that EcomDev_PHPUnit manages the configuration (and the fact that it's not caching) it
which is critical for Aoe_Scheduler since job configuration can also live in config.

In addition to that the tests coming with Aoe_Scheduler are integration tests. That means some of them will actually trigger
`scheduler.php` and this is where we can't have a separate test environment the runs the tests if that same test environment
won't be available when executing the jobs. 

Going back to plain PHPUnit reduces the complexity of testing significantly and allows to have cleaner and more stable test cases.

### NOTE

Please note that your tests will run on your main databases. It's not recommended to run the tests on a production database
since jobs will be created, deleted and executed during the testing process

