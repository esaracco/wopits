### Crons

> ***Do not forget to add wopits crons to crontab!***

Create a `/var/log/wopits.domain.com/` directory and customize the following lines before adding them into the wopits user crontab:
```bash
1 0 * * * /var/www/wopits.domain.com/app/crons/cleanup.php >> /var/log/wopits.domain.com/cleanup.log 2>&1
1 0 * * * /var/www/wopits.domain.com/app/crons/check-deadline.php >> /var/log/wopits.domain.com/check-deadline.log 2>&1
*/15 * * * * /var/www/wopits.domain.com/app/crons/ping.php >> /var/log/wopits.domain.com/ping.log 2>&1
* * * * * /var/www/wopits.domain.com/app/crons/processEmailsQueue.php >> /var/log/wopits.domain.com/processEmailsQueue.log 2>&1
