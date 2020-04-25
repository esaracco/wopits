<?php
  // DO NOT EDIT OR DELETE THIS FILE!
  //
  // - If you intend to deploy wopits, use "app/deploy/config.php" instead.
  // - If you are using wopits "as is" from the Git repository, duplicate this
  //   fil to "site-config.php" and customize it according to your needs.

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
