wopits - _A world of post-its_
==============================

<p align="center"><img src="https://www.wopits.com/img/wopits.png"/></p>

>***This manual is in beta state. Do not hesitate to help us improve it according to your experience!***

[Project homepage](https://wopits.esaracco.fr) | [wopits homepage](https://www.wopits.com)

*wopits is a world in which you can manage all kinds of projects by simply using post-its to share and collaborate with other users simultaneously*.

If you do not want to bother installing it on your own, just create an account on the [wopits official website](https://www.wopits.com)!

INSTALLATION
------------

> You will only need Apache, MySQL and PHP 7 to make it work!
> Even deployment is not required (although recommended): you can directly use the directory `www/` of the Git repository as Apache DocumentRoot.

- `git clone git@github.com:esaracco/wopits.git`.
- Install Apache, MariaDB or MySQL and PHP 7 (with `php-gettext`, `php-mysql`, `php-imagick`, `php-zip` and optionally `php-ldap`). `php-ldap` will be required only if you intend to use LDAP authentication.
- Configure Apache by customizing `/app/doc/apache/wopits-example.conf`. Enable `mod_ssl`, `mod_rewrite`, `mod_headers`, `mod_proxy` and `mod_proxy_wstunnel` Apache modules.
- Configure SSL using Let's Encrypt or whatever Certificate Authority.
- Create a database and a user (using the `app/db/wopits-create_db.example.sql` file if necessary). Then create tables using `app/db/wopits-create_tables.sql`:
```bash
# mysql [your database] < app/db/wopits-create_tables.sql
```
- If you intend to use wopits Git repository "as is" as your Apache DocumentRoot, duplicate `site-config.template.php` in `site-config.php` and customize it.

### Server optimization

First of all, install wopits on a decently sized server with at least 16GB of RAM, good bandwidth and high-performance I/O disks.

#### MySQL

It is important to optimize the MySQL configuration as much as possible. Default settings will give poor results.

#### PHP

Edit PHP configuration file for both apache and CLI and remove the memory limit allowed for scripts `memory_limit = -1` or increase it significantly depending on available RAM.

- http://socketo.me/docs/deploy#evented-io-extensions
- https://github.com/andreybolonin/RatchetBundle/blob/master/README.md

To allow PHP to use more than 1024 file descriptors and to be more performant at network level, do the following:

 1. Install the `ev` and `event` modules using `pecl`. You will need `libevent-dev`, `php-dev` and `php-pear` in order to install and compile those modules.
 2. Add to `/etc/security/limits.d/local.conf`:
```bash
*               soft    nofile          1000000
*               hard    nofile          1000000
```
 3. Add to `/etc/sysctl.d/local.conf`:
```bash
fs.file-max=1000000
```

BUILD & DEPLOYMENT
------------------

> wopits can be used "as is" from the Git repository, without any deployment. However, we highly recommend to do this only if you want to contribute! **For production use it is better to deploy it** (local or remote, whatever).

### WebSocket server

In order to run the WebSocket server as a daemon you must add it to the startup scripts. Depending on your system, the procedure may vary. We will describe here the basics for **systemd**:

 1. Create `/etc/systemd/system/wopits.service` (using the `app/doc/systemd/wopits-example.service` template file).
 2. Execute `systemctl start wopits` and `systemctl enable wopits`.
 3. To see the daemon logs, use `journalctl -f -u wopits`.

**Make sure that the WebSocket server daemon's group can write to the `data/` directory** of the wopits DocumentRoot. **It must be the same as the wopits `data/` directory group** (which has been chmoded with 2770).

> ***Do not forget to restart this daemon after each deployment!***

### Directly from the Git repository

If you are using the Git repository as your Apache DocumentRoot, create a `data/` directory and give the Apache user all rights on it:
```bash
# mkdir -p data/{walls,users}
# chown -R [Apache user]:[Apache user] data
# chmod 2770 data
```

### Deployment

- Move to `app/deploy/`.
- Create a new file `config.php` in this directory by duplicating `config.example.php`, and customize it.
- In order to minify JS, CSS or HTML, you will need to customize the `minifiers` section. Read `config.example.php` for more information.
- Deploy the application by executing `./deploy -e[yourenv]`. `yourenv` should have been defined in the new `config.php` you just created previously. If the target is located on remote, the SSH user must have full rights on the remote DocumentRoot.
- The very first time the deployment script has been executed, you will have to log as root and execute the following commands before creating a service for the wopits WebSocket daemon:
```bash
# cd [DocumentRoot]
# mkdir -p data/{walls,users}
# chown -R [Apache user]:[Apache user] data
# chmod 2770 data
```
- At each deployment you must broadcast new release announce to all connected clients, reload apache and restart the WebSocket daemon.
```bash
$ /var/www/wopits.domain.com/app/websocket/client.php -n
# systemctl reload apache2
# systemctl restart wopits
```
The post-deployment script will do this for you (execute it as root):
```bash
# /var/www/wopits.domain.com/app/deploy/bin/post-deploy.php
```

#### DKIM

> The use of DKIM for sending emails is not mandatory, but highly recommended if your SMTP server does not already manage it.

The wopits DKIM private key must be placed in `app/dkim/dkim.private`.
You can generate a pair of keys using:
```bash
$ openssl genrsa -out dkim.private 1024
$ openssl rsa -in dkim.private -out dkim.public -pubout -outform PEM
```
If you are using wopits as is, without depoyment, you must edit `site-config.php`:
```php
define ('WPT_USE_DKIM', true);
define ('WPT_DKIM_DOMAIN', 'domain.com');
define ('WPT_DKIM_SELECTOR', 'mail');
```
If you plan to deploy wopits, edit `app/deploy/config.php` and customize the following lines before adding them into your deployment environment settings:
```php
'dkim' => [
  'domain' => 'domain.com',
  'selector' => 'mail'
]
```
**The following is needed only after the very first deployment, or later if you update your key**:
Copy your private key in `app/dkim/dkim.private` on the target. Then:
```bash
# cd [DocumentRoot]
# chown [Apache user] app/dkim/dkim.private
# chmod 400 app/dkim/dkim.private
```

### LDAP

> ***Do not forget to install the PHP module `php-ldap`!***

You can tell wopits to use a LDAP directory for users authentication. In this case, users who are not in your directory will not be able to create a account on your wopits instance.

Edit `app/deploy/config.php` and customize the following lines before adding them into your deployment environment settings:
```php
'ldap' => [
  'host' => 'ldaps://ldap.domain.com:636',
  'binddn' => 'uid=wopits,ou=sysaccounts,o=domain.com',
  'bindpw' => 'ChangeMe',
  'objectclass' => 'people',
  'basedn' => 'o=domain.com'
]
```

#### Synchronize your LDAP users

Synchronization is not required in order to run wopits with LDAP, however it is recommended that you synchronized regularly all your LDAP users. Doing so, they will always be available in full for wall sharing.

After deploying wopits, run the script `app/ldap/synchro.php` in order to synchronize LDAP users into the wopits database. The best way to keep your wopits database up to date is to cron this script.

> ***Only run the synchronization script after configuring and deploying wopits!***

### Crons

> ***Do not forget to add wopits crons to crontab!***

Create a `/var/log/wopits.domain.com/` directory and customize the following lines before adding them into the wopits user crontab:
```bash
1 0 * * * /var/www/wopits.domain.com/app/crons/cleanup.php >> /var/log/wopits.domain.com/cleanup.log 2>&1
1 0 * * * /var/www/wopits.domain.com/app/crons/check-deadline.php >> /var/log/wopits.domain.com/check-deadline.log 2>&1
*/20 * * * * /var/www/wopits.domain.com/app/crons/ping.php >> /var/log/wopits.domain.com/ping.log 2>&1
