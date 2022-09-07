<?php
  define ('ENV', [

    // Paths and args for minifiers.
    // (let this section empty if you do not want to minify)
    // WARNING : test them before using them here, as minifiers can sometimes
    //           cause malfunctions! However, default options below are known
    //           to work pretty well with wopits
    //
    // You will find some common minifiers here:
    // - JS   : https://dl.google.com/closure-compiler/compiler-latest.zip
    // - CSS  : https://github.com/google/closure-stylesheets/releases
    // - HTML : https://code.google.com/archive/p/htmlcompressor/downloads
    //          https://github.com/kangax/html-minifier
    //
    // -> If minifier needs arguments, put them here too.
    //
    'minifiers' => [
      // WARNING : the JS minifier must understand ECMAScript 6!
      'js' => __DIR__.'/bin/closure-compiler-v20200517.jar',
      'css' => __DIR__.'/bin/closure-stylesheets.jar',
      'html' => __DIR__.'/bin/htmlcompressor-1.5.3.jar'
      // WARNING : use only the following options with html-minifier!
      //'html' => __DIR__.'/bin/html-minifier --collapse-whitespace --remove-comments --remove-optional-tags'
    ],

    // REMOTE target example
    'wopits1' => [
      // Wopits URL
      'url' => 'https://www.wopits.com',
      // The user set in wopits ws & task systemd units conf
      'service-user' => 'wopits',
      // Apache user on target host
      'www-system-user' => 'www-data',
      // Local working directory
      'build-path' => '/tmp/wopits-build',
      // Information about target on which application will be installed
      'target' => [
        // "local" or "remote"
        'type' => 'remote',
        // * If remote, "path" must be a ssh argument containing remote user
        //   and the apache DocumentRoot on remote host (user@host:/path).
        //   the SSH user must have full rights on the remote DocumentRoot.
        // * If local, "path" must be the apache document root
        'path' => 'root@www.wopits.com:/var/www/www.wopits.com'
      ],
      // Log path
      'log-path' => '/var/log/wopits',
      // Log details
      'log-details' => true,
      // WebSocket server
      'websocket' => [
        'server' => [
          // Port of the WebSocket server (must be se same as the one you
          // used in your apache proxy section conf)
          'port' => 8080
        ]
      ],
      // Task server
      'task' => [
        'server' => [
          // Port of the task server.
          'port' => 9501
        ]
      ],
      // SMTP if empty, localhost will be used by default.
      'smtp' => [
        'host' => 'smtp.free.fr',
        'port' => 25
      ],
      // Database on target
      'db' => [
        'dsn' => 'mysql:host=localhost;dbname=wopits-prod;port=3306',
        'user' => 'wopits-prod',
        'password' => 'ChangeMe1',
      ],
      // System commands
      'cmd' => [
        // System command(s) to restart apache
        'apache-restart' =>
          'systemctl restart apache2;'.
          'systemctl reload php-fpm.service',
        // System commands to restart wopits daemons
        'wopits-ws-restart' => 'systemctl restart wopits1-ws',
        'wopits-task-restart' => 'systemctl restart wopits1-task'
      ],
      // Enable/disable wopits support campaign
      'support_campaign' => false,
      // About popup
      // Display/hide about popup informations
      'about' => [
        // "Warning" section
        'warning' => true,
        // "Privacy policy" section
        'privacy' => true
      ],
      // Welcome message on login page
      // Display/hide welcome message.
      'login_welcome' => false,
      // Emails
      'emails' => [
        'from' => 'contact@wopits.com',
        'unsubscribe' => 'unsubscribe@wopits.com',
        'contact' => 'contact@wopits.com'
      ],
      // DKIM
      // (let this section empty if you do not want to use LDAP)
      // If you use LDAP for authentication, users who are not registered in
      // LDAP will not be able to create a account on wopits.
      'ldap' => [
        'host' => 'ldaps://ldap.domain.com:636',
        'binddn' => 'uid=wopits,ou=sysaccounts,o=domain.com',
        'bindpw' => 'ChangeMe1',
        // The object class where to search for users.
        // Must inherit from InetOrgPerson and the following attributes are
        // required: "dn", "uid", "cn" and "mail".
        'objectclass' => 'people',
        // Base DN for users search
	'basedn' => 'o=domain.com',
	// Filter for accepted users, will default to objectclass if empty
        'filter' => ''
      ],
      // DKIM
      // (let this section empty if you do not want to use DKIM)
      'dkim' => [
        // Your wopits domain
        'domain' => 'domain.com',
        // The selector you entered when you configured your DNS TXT record
        'selector' => 'mail'
      ]
    ],

    // LOCAL target example
    'wopits2' => [
      // Wopits URL
      'url' => 'https://wopits-preprod.domain.com',
      // The user set in wopits ws & task systemd units conf
      'service-user' => 'wopits',
      // Apache user on target host
      'www-system-user' => 'www-data',
      // Local working directory
      'build-path' => '/tmp/wopits-build',
      // Information about target on which application will be installed
      'target' => [
        // "local" or "remote"
        'type' => 'local',
        // * If remote, "path" must be a ssh argument containing remote user
        //   and the apache DocumentRoot on remote host (user@host:/path).
        //   the SSH user must have full rights on the remote DocumentRoot.
        // * If local, "path" must be the apache document root
        'path' => '/var/www/wopits-preprod.domain.com'
      ],
      // Log path
      'log-path' => '/var/log/wopits',
      // Log details
      'log-details' => true,
      // WebSocket
      'websocket' => [
        'server' => [
          // Port of the WebSocket server (must be se same as the one you
          // used in your apache proxy section conf)
          'port' => 8081
        ]
      ],
      // SMTP if empty, localhost will be used by default.
      'smtp' => [
        'host' => 'smtp.free.fr',
        'port' => 25
      ],
      // Database on target
      'db' => [
        'dsn' => 'mysql:host=localhost;dbname=wopits-preprod;port=3306',
        'user' => 'wopits-preprod',
        'password' => 'ChangeMe2',
      ],
      // System commands
      'cmd' => [
        // System command(s) to reload apache
        'apache-restart' => 'systemctl reload apache2',
        // System commands to restart wopits daemons
        'wopits-ws-restart' => 'systemctl restart wopits2-ws',
        'wopits-task-restart' => 'systemctl restart wopits2-task'
      ],
      // About popup
      // Display/hide about popup informations
      'about' => [
        // "Warning" section
        'warning' => false,
        // "Privacy policy" section
        'privacy' => true
      ],
      // Welcome message on login page
      // Display/hide welcome message.
      'login_welcome' => true,
      // Emails
      'emails' => [
        'from' => 'team@domain.com',
        'unsubscribe' => 'unsubscribe@domain.com',
        'contact' => 'team@domain.com'
      ],
      // DKIM
      // (let this section empty if you do not want to use DKIM)
      'dkim' => [
        // Your wopits domain
        'domain' => 'domain.com',
        // The selector you entered when you configured your DNS TXT record
        'selector' => 'mail'
      ]
    ]

  ]);
?>
