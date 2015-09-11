## Tests

Add some information on how to run tests (e.g. with MageTestStand).

Note: since some of the tests are integration tests and I want to check if cron is processing jobs correctly a separate test database can't be used (since cron.sh and the other scripts wouldn't find any scheduled jobs).

Don't run the test on production since the schedules table will be flushed and new cron jobs might be created during the tests.

MageTestStand currently doesn't support preparing EcomDev_Phpunit to allow running tests on the same database.