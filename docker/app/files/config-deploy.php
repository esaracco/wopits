<?php
  define ('ENV', [

/* Overkill: no need to minify for this experimental docker

    'minifiers' => [
      // WARNING : the JS minifier must understand ECMAScript 6!
      'js' => __DIR__.'/bin/closure-compiler.jar',
      'css' => __DIR__.'/bin/closure-stylesheets.jar',
      'html' => __DIR__.'/bin/htmlcompressor.jar'
    ],
*/

    'docker' => [
      'url' => 'https://WOPITS_HOST'.(($v=getenv('WOPITS_HTTPS_PORT'))?":$v":':40000'),
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
        'path' => '/var/www/wopits.localhost'
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
          'port' => 8080
        ]
      ],
      'task' => [
        'server' => [
          'port' => 9501
        ]
      ],
      'cmd' => [
        'apache-restart' => 'service apache2 restart',
        'wopits-ws-restart' => '/var/www/wopits.localhost/app/services/websocket/server-ws.php',
        'wopits-task-restart' => '/var/www/wopits.localhost/app/services/task/server-task.php',
      ], 
      // SMTP if empty, localhost will be used by default.
      'smtp' => [
        'host' => 'smtp.free.fr',
        'port' => 25
      ],
      // Database on target
      'db' => [
        'dsn' => 'mysql:host=db;dbname=WOPITS_DB_NAME;port=3306',
        'user' => 'WOPITS_DB_USER',
        'password' => 'WOPITS_DB_PASSWORD',
      ],
      // About
      'about' => [
        'warning' => true,
        'privacy' => true
      ],
      // Login welcome message
      'login_welcome' => true,
      // Emails
      'emails' => [
        'from' => ($v=getenv('WOPITS_EMAILS_FROM'))?$v:'contact@wopits.localhost',
        'unsubscribe' => ($v=getenv('WOPITS_EMAILS_UNSUBSCRIBE'))?$v:'unsubscribe@wopits.localhost',
        'contact' => ($v=getenv('WOPITS_EMAILS_CONTACT'))?$v:'contact@wopits.localhost'
      ]
    ]
  ]);
?>
