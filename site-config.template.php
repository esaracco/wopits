<?php
  // DO NOT EDIT OR DELETE THIS FILE!
  //
  // - If you intend to deploy wopits, use "app/deploy/config.php" instead.
  // - If you are using wopits "as is" from the Git repository, duplicate this
  //   file to "site-config.php" and customize it.

  // Your wopits secret key for data integrity check
  // -> Set it once and for all and don't change it anymore!
  define ('WPT_SECRET_KEY', '!!tobechanged!!');

  // System command to reload apache
  define ('WPT_APACHE_RESTART', 'systemctl reload apache2');
  // System command to restart wopits daemon
  define ('WPT_WOPITS_RESTART', 'systemctl restart wopits-example');

  // Database
  define ('WPT_DSN', 'mysql:host=localhost;dbname=wopits-example;port=3306');
  define ('WPT_DB_USER', 'wopits-example');
  define ('WPT_DB_PASSWORD', '!!tobechanged!!');

   // Websockets server
  define ('WPT_WS_PORT', 8080);

  // SMTP
/*TODO //FIXME For the moment, the mailer can only be the localhost
  -> Do not change those values
  define ('WPT_SMTP_HOST', 'localhost');
  define ('WPT_SMTP_PORT', 25);
*/

  // About popup
  // Display/hide about popup informations
  // "Warning" section
  define ('WPT_ABOUT_WARNING', false);
  // "Privacy policy" section
  define ('WPT_ABOUT_PRIVACY', false);

  // Emails
  define ('WPT_EMAIL_FROM', 'noreply@domain.com');
  define ('WPT_EMAIL_CONTACT', 'contact@domain.com');

  // DKIM
  // I you want to use DKIM and have well configured it
  define ('WPT_USE_DKIM', true);
  // Domain
  define ('WPT_DKIM_DOMAIN', 'domain.com');
  // Selector
  define ('WPT_DKIM_SELECTOR', 'mail');
?>
