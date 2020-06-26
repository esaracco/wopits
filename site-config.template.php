<?php
  // DO NOT EDIT OR DELETE THIS FILE!
  //
  // - If you intend to deploy wopits, use "app/deploy/config.php" instead.
  // - If you are using wopits "as is" from the Git repository, duplicate this
  //   file to "site-config.php" and customize it.

  // System command to reload apache
  // Example: "systemctl reload apache2;systemctl reload php-fpm.service"
  define ('WPT_APACHE_RESTART', "");
  // System command to restart wopits daemon
  // Example: systemctl restart wopits
  define ('WPT_WOPITS_RESTART', "");

  // Database
  // Example: mysql:host=localhost;dbname=wopits;port=3306
  //          pgsql:host=localhost;dbname=wopits;port=5433
  define ('WPT_DSN', "");
  // Example: wopits
  define ('WPT_DB_USER', "");
  // Example: !!tobechanged!!
  define ('WPT_DB_PASSWORD', "");

   // Websockets server
  // Example: 8080
  define ('WPT_WS_PORT', "");

  // SMTP
/*TODO //FIXME For the moment, the mailer can only be the localhost
  -> Do not change those values
  define ('WPT_SMTP_HOST', "localhost");
  define ('WPT_SMTP_PORT', "25");
*/

  // About popup
  // Display/hide about popup informations
  // "Warning" section
  // Example: false
  define ('WPT_ABOUT_WARNING', "");
  // "Privacy policy" section
  // Example: false
  define ('WPT_ABOUT_PRIVACY', "");

  // Emails
  // Example: noreply@domain.com
  define ('WPT_EMAIL_FROM', "");
  // Example: contact@domain.com
  define ('WPT_EMAIL_CONTACT', "");

  // LDAP
  // If true, LDAP will be used for users authentication. Users who are not
  // registered in LDAP will not be able to create account on wopits.
  // Example: true
  define ('WPT_USE_LDAP', "");
  // LDAP host
  // Example: ldaps://ldap.domain.com:636
  define ('WPT_LDAP_HOST', "");
  // DN for LDAP wopits authentication
  // Example: uid=wopits,ou=sysaccounts,o=domain.com
  define ('WPT_LDAP_BINDDN', "");
  // Password for LDAP wopits user authentication
  // Example: !!tobechanged!!
  define ('WPT_LDAP_BINDPW', "");
  // Base DN for users search
  // Example: o=domain.com
  define ('WPT_LDAP_BASEDN', "");
  // The object class where to search for users.
  // Must inherit from InetOrgPerson and the following attributes are required:
  // "dn", "uid", "cn" and "mail".
  // Example: people
  define ('WPT_LDAP_OBJECTCLASS', "");

  // DKIM
  // I you want to use DKIM and have well configured it
  // Example: true
  define ('WPT_USE_DKIM', "");
  // Domain
  // Example: domain.com
  define ('WPT_DKIM_DOMAIN', "");
  // Selector
  // Example: mail
  define ('WPT_DKIM_SELECTOR', "");

  // Control dev mode or prod mode
  // Example: false
  define ('WPT_DEV_MODE', "");
?>
