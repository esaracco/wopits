wopits - _A world of post-its_
==============================

[Project homepage](https://wopits.esaracco.fr) | [wopits homepage](https://www.wopits.com)

<table>
  <tr>
    <td><img src="https://www.wopits.com/img/wopits.png"></td>
    <td>wopits is a multilingual free software under GPL license with which you can manage all kinds of projects just using sticky notes to share and collaborate with other users simultaneously.</td>
  </tr>
</table>

You can edit several walls of sticky notes at the same time, add attachments, insert images, create relationships etc.
Groups management makes it possible to finely control the sharing of data, and a chat is available for each wall.

If you don't want to bother installing wopits yourself, just create an account on the [official wopits website](https://www.wopits.com)!

*wopits is **Node.js free** and uses [Swoole](https://www.swoole.co.uk) as a WebSocket & Task server + [Redis](https://redis.io/) for the management of volatile data.*

INSTALLATION
------------

> You will need PHP, Apache, MariaDB or PostgreSQL, Redis & Swoole to make it work.

### On the source host

The following actions have to be done on the machine you installed the Git repository. Which is not necessarily the target host where the wopits website will run.

- `git clone git@github.com:esaracco/wopits.git`.
- Install composer >= 1.8.4 & yarn >= 1.13.0. If you are using Debian **do not install the cmdtest package**. Remove it instead, and proceed like this:
```bash
# apt install nodejs npm
# curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
# echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
# apt update
# apt install yarn
```
See https://classic.yarnpkg.com/en/docs/install for help installing yarn.

### On the target host

The following actions have to be done on the machine that will host the wopits website. Which is not necessarily the source machine where you installed the Git repository.

- Install [Swoole](https://github.com/swoole/swoole-src#2-install-from-source-recommended) from the [latest 4.4.x tag](https://github.com/swoole/swoole-src/tags) and activate it for both CLI and Apache. Then tweak `enable_preemptive_scheduler`:
```ini
swoole.enable_preemptive_scheduler=On
```
- Install Apache >= 2.4.38, MariaDB >= 10.3.23 or PostgreSQL >= 11.7, Redis >= 5.0.3 and PHP >= 7.3.19 (with `php-gettext`, `php-mysql`, `php-pgsql`, `php-imagick`, `php-zip` and optionally `php-ldap`). `php-ldap` will be required only if you intend to use LDAP authentication. Similarly, install `php-mysql` or `php-pgsql` depending on the SGBD you want to use.
- Configure Apache by customizing `/app/doc/apache/wopits.domain.com.conf`. Enable `mod_ssl`, `mod_rewrite`, `mod_headers`, `mod_proxy` and `mod_proxy_wstunnel` Apache modules.
- Configure SSL using Let's Encrypt or whatever Certificate Authority.
- Create a user and a database (using the `app/db/mysql/wopits-create_db.example.sql` (MariaDB) or `app/db/postgresql/wopits-create_db.example.sh` (PostgreSQL) file after having customize it according to your needs). Then create tables using `app/db/*/wopits-create_tables.sql`:

- With **MariaDB**:

```bash
$ sudo -uroot mysql < app/db/mysql/wopits-create_db.example.sql
$ mysql wopits -uwopits -p < app/db/mysql/wopits-create_tables.sql
```
- With **PostgreSQL**:

Edit the `pg_hba.conf` like this:

1. According to your need, update:

```bash
# "local" is for Unix domain socket connections only
local   all             all                                     peer
```

2. to:

```bash
# "local" is for Unix domain socket connections only
local   all             all                                     md5
```

3. then:

```bash
$ sudo -upostgres app/db/postgresql/wopits-create_db.example.sh
$ psql wopits -Uwopits -W < app/db/postgresql/wopits-create_tables.sql
```
- If you intend to use wopits Git repository "as is" as your Apache DocumentRoot, duplicate `site-config.template.php` in `site-config.php` and customize it.

### Server optimization

First of all, install wopits on a decently sized server with at least 16GB of RAM, good bandwidth and high-performance I/O disks.

#### MariaDB / PostgreSQL

It is important to optimize the SGBD configuration as much as possible. Default settings will give poor results.

#### PHP

Edit PHP configuration file for apache and tweak the `post_max_size`:
```ini
post_max_size = 20M
```
Edit PHP configuration file for both apache and CLI and remove the memory limit allowed for scripts:
```ini
memory_limit = -1
```
or increase it significantly depending on available RAM.

Enable and customize PHP OPcache module for both **apache and CLI** (very important). Here is a common customization:

```ini
opcache.enable=1
opcache.enable_cli=1
opcache.validate_timestamps=0
opcache.revalidate_freq=60
opcache.save_comments=0
opcache.enable_file_override=1
```

To allow PHP to use more than 1024 file descriptors and to be more performant at network level, do the following:

- http://socketo.me/docs/deploy#evented-io-extensions
- https://github.com/andreybolonin/RatchetBundle/blob/master/README.md

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

 1. Create a `/var/log/wopits/` directory and give the wopits user full rights on it.
 2. Create `/etc/systemd/system/wopits-ws.service` (using the `app/doc/systemd/wopits-ws.service` template file).
 3. Execute `systemctl start wopits-ws` and `systemctl enable wopits-ws`.
 4. To see the daemon logs, use `journalctl -fu wopits-ws`. To see the WebSocket server logs, open `/var/log/wopits/server-ws.log`.

**Make sure that the WebSocket server daemon's group can write to the `data/` directory** of the wopits DocumentRoot. **It must be the same as the wopits `data/` directory group** (which has been chmoded with 2770).

### Task server

In order to run the task server as a daemon you must add it to the startup scripts. Depending on your system, the procedure may vary. We will describe here the basics for **systemd**:

 1. Create a `/var/log/wopits/` directory and give the wopits user full rights on it.
 2. Create `/etc/systemd/system/wopits-task.service` (using the `app/doc/systemd/wopits-task.service` template file).
 3. Execute `systemctl start wopits-task` and `systemctl enable wopits-task`.
 4. To see the daemon logs, use `journalctl -fu wopits-task`. To see the Task server logs, open `/var/log/wopits/server-task.log`.

**Make sure that the task server daemon's group can write to the `data/` directory** of the wopits DocumentRoot. **It must be the same as the wopits `data/` directory group** (which has been chmoded with 2770).

### From the Git repository

If you are using the Git repository as your Apache DocumentRoot without deployment:

 1. create a `data/` directory and give the Apache user all rights on it:
```bash
# mkdir -p data/{walls,users}
# chown -R [ApacheUser]:[wopitsUserGroup] data
# chmod 2770 data
```
 2. Install external PHP modulesi using `composer`:
```bash
$ cd app/libs/
$ composer update
```
 3. Install external Javascript modules using `yarn`:
```bash
$ cd www/libs/
$ yarn
```

### Deployment

- Move to `app/deploy/`.
- Create a new file `config.php` in this directory by duplicating `config.example.php`, and customize it.
- In order to minify JS, CSS or HTML, you will need to customize the `minifiers` section. Read `config.example.php` for more information.
- Deploy the application by executing `./deploy -e[yourenv]`. `yourenv` should have been defined in the new `config.php` you just created previously. If the target is located on remote, the SSH user must have full rights on the remote DocumentRoot.
- The very first time the deployment script has been executed, you will have to log as root and execute the following commands before creating a service for the wopits WebSocket daemon:
```bash
# chown -R [wopitsUser]:[wopitsUserGroup] /var/www/wopits.domain.com/
# cd /var/www/wopits.domain.com/
# mkdir -p data/{walls,users}
# chown -R [ApacheUser]:[wopitsUserGroup] data
# chmod 2770 data
```
Right after each deployment you must execute as soon as possible the following post-deployment script **as root**:
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
# cd /var/www/wopits.domain.com/
# chown [ApacheUser]:[wopitsUserGroup] app/dkim/dkim.private
# chmod 440 app/dkim/dkim.private
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

Create a `/var/log/wopits/` directory and customize the following lines before adding them into the wopits user crontab:
```bash
1 0 * * * /var/www/wopits.domain.com/app/crons/cleanup.php >> /var/log/wopits/cleanup.log 2>&1
1 0 * * * /var/www/wopits.domain.com/app/crons/check-deadline.php >> /var/log/wopits/check-deadline.log 2>&1
*/15 * * * * /var/www/wopits.domain.com/app/crons/ping.php >> /var/log/wopits/ping.log 2>&1
