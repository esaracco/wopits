<?php

// Version
define ('WPT_VERSION', '0.37alpha2');
define ('WPT_EXPORT_MIN_VERSION', '0.21');

// Important!
// Apache and wopits WebSocket server manipulate the same files.
// They must have the same system group and umask must be 002.
umask (002);

// Autoloader for external libs.
if (file_exists (__DIR__.'/libs/vendor/autoload.php'))//WPTPROD-remove
  require_once (__DIR__.'/libs/vendor/autoload.php');

// Autoloader for wopits classes.
spl_autoload_register (function ($class)
  {
    require_once (
      __DIR__.'/class/'.str_replace ('\\', '/', substr ($class, 7)).'.php');
  });

require (
  //<WPTPROD-remove>
    (!file_exists (__DIR__.'/../site-config.php')) ?
      __DIR__.'/../site-config.template.php' :
  //</WPTPROD-remove>
    __DIR__.'/../site-config.php');

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

// Users groups types
define ('WPT_GTYPES_DED', 1);
define ('WPT_GTYPES_GEN', 2);

// Walls access rights
define ('WPT_WRIGHTS_ADMIN', 1);
define ('WPT_WRIGHTS_RW', 2);
define ('WPT_WRIGHTS_RO', 3);

// Themes
define ('WPT_THEMES', ['blue', 'green', 'red', 'orange', 'purple']);

// Size of the Swoole volatile session tables.
define ('WPT_SWOOLE_TABLE_SIZE', 1024);

// WebSocket server sections
define ('WS_SERVER_SECTIONS', [
  'clients', 'openedWalls', 'activeWalls', 'chatUsers']);

//////////////// This section will be removed after deployment //////////////

//<WPTPROD-remove>

  // Max upload file size in Mb
  define ('WPT_UPLOAD_MAX_SIZE', 10);
  // Max size for wopits export/import file in Mb
  define ('WPT_IMPORT_UPLOAD_MAX_SIZE', 500);

  // Max cells in a wall (for performance reasons)
  define ('WPT_MAX_CELLS', 400);

  // Timeouts (seconds).
  define ('WPT_TIMEOUTS', [
    // AJAX requests.
    'ajax' => 10,
    // Edition without activity.
    'edit' => 15
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
      'createWall',
      'openWall',
      'wallProperties',
      'wallUsersview',
      'userView',
      'postitAttachments',
      'postitView',
      'postitUpdate',
      'updateOneInput',
      'info',
      'confirm',
      'datePicker',
      'themeChooser'
    ]
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
    'datePicker' => [],
    'chatroom' => [],
    'filters' => [],
    'arrows' => [],
    'main' => [],
    'editable' => [],
    'header' => [],
    'postit' => [],
    'cell' => [],
    'events' => [],
    'login' => [],
    'account' => [],
    'shareWall' => [],
    'usersSearch' => [],
    'postitsSearch' => [],
    'openWall' => [],
    'wallProperties' => []
  ]);

//</WPTPROD-remove>

?>
