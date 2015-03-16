cron.sh vs cron.php vs scheduler_cron.sh

Recommendet frequency

always vs default

Not in webserver context (no wget php)

How to correctly configure cron?
Note: Correct user!
Also check: http://www.magentocommerce.com/knowledge-base/entry/ce18-and-ee113-installing#install-cron
NOT: http://stackoverflow.com/questions/5644037/setting-up-cron-job-in-magento?rq=1
Add note to not run this via wget 
- cron is not supposed to run in web context
	- different php settings, max_execution time, memory_limit (and don't increase these globally only to satisfy cron.php)
