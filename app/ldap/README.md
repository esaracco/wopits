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
