<?php
  require (
    (!file_exists (__DIR__.'/../site-config.php')) ?//PROD-remove
      __DIR__.'/../site-config.template.php' ://PROD-remove
      __DIR__.'/../site-config.php');

  define ('WPT_DEV_MODE', true);
  define ('WPT_VERSION', '0.3alpha1');

  define ('WPT_COOKIE', 'wopits');

  // Paths
  define ('WPT_ROOT_PATH', realpath (__DIR__.'/..'));
  define ('WPT_DATA_SPATH', WPT_ROOT_PATH.'/data');
  define ('WPT_DATA_WPATH', '/data');

  // Locales
  define ('WPT_LOCALES', [
    'en' => 'Europe/London',
    'fr' => 'Europe/Paris'
  ]);
  define ('WPT_DEFAULT_LOCALE', 'en');

  // Max upload file size in Mb
  define ('WPT_UPLOAD_MAX_SIZE', 10);
  // Max size for wopits export/import file in Mb
  define ('WPT_IMPORT_UPLOAD_MAX_SIZE', 500);

  // Users groups types
  define ('WPT_GTYPES', [
    'dedicated' => 1,
    'generic' => 2
   ]);

  // Walls access rights
  define ('WPT_RIGHTS', [
    'walls' => [
      'admin' => 1,
      'rw' => 2,
      'ro' => 3
    ]
  ]);

  // Themes
  define ('WPT_THEMES', ['blue', 'green', 'red', 'orange']);

  // Edit queue timeout (seconds)
  define ('WPT_TIMEOUTS', [
    'ajax' => 10
  ]);

  // Modules
  define ('WPT_MODULES', [
    'common' => [],
    'settings' => [],
    // Postit tags picker
    'tagPicker' => [
      'items' => [
        'thumbs-up' => '#555',
        'thumbs-down' => '#555',
        'tools' => '#555',
        'life-ring' => '#555',
        'peace' => '#555',
        'phone' => '#555',
        'ambulance' => '#555',
        'balance-scale' => '#555',
        'bell' => '#555',
        'bullhorn' => '#555',
        'certificate' => '#555',
        'cubes' => '#555',
        'donate' => '#555',
        'fire-extinguisher' => '#555',
        'fire' => '#555',
        'birthday-cake' => '#555',
        'users' => '#555',
        'lightbulb' => '#555'
      ]
    ],
    // Postit colors picker
    'colorPicker' => [
      'items' => [
        'yellow' => '#ffffc6',
        'orange' => '#ffdd9f',
        'green' => '#b3ffc4',
        'red' => '#ff9fa8',
        'blue' => '#9ef2ff',
        'gray' => '#c8d0d7'
      ] 
    ],
    'chatroom' => [],
    'filters' => [],
    'arrows' => [],
    'main' => [],
    'header' => [],
    'postit' => [],
    'cell' => [],
    'events' => [],
    'login' => [],
    'account' => [],
    'shareWall' => [],
    'usersSearch' => [],
    'postitsSearch' => [],
    'openWall' => []
  ]);

  // Popups
  define ('WPT_POPUPS', [
    'login' => [
      'about',
      'userGuide',
      'settings',
      'createAccount',
      'resetPassword',
      'info'
    ],
    'index' => [
      'plug',
      'about',
      'userGuide',
      'settings',
      'account',
      'shareWall',
      'group',
      'usersSearch',
      'groupAccess',
      'postitsSearch',
      'changePassword',
      'openWall',
      'wallProperties',
      'wallUsersview',
      'userView',
      'postitAttachments',
      'postitView',
      'postitUpdate',
      'updateOneInput',
      'info',
      'confirm'
    ]
  ]);

?>
