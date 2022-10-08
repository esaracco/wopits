<?php

define('WPT_VERSION', '0.64-alpha.15');
define('WPT_LAST_UPDATE', '2022-10-08');
define('WPT_DISPLAY_LATEST_NEWS', false);
define('WPT_EXPORT_MIN_VERSION', '0.21');

// Important!
// Apache and wopits WebSocket server manipulate the same files.
// They must have the same system group and umask must be 002.
umask(002);

// Autoloader for external libs
/*
if (file_exists(__DIR__.'/libs/vendor/autoload.php'))//WPTPROD-remove
  require_once(__DIR__.'/libs/vendor/autoload.php');
*/

// Autoloader for wopits classes
spl_autoload_register(function($class) {
  require_once (
      __DIR__.'/class/'.str_replace('\\', '/', substr($class, 7)).'.php');
});

require (
  //<WPTPROD-remove>
    !file_exists(__DIR__.'/../site-config.php') ?
        __DIR__.'/../site-config.template.php' :
  //</WPTPROD-remove>
        __DIR__.'/../site-config.php'
);

// Paths
define('WPT_ROOT_PATH', realpath(__DIR__.'/..'));
define('WPT_DATA_SPATH', WPT_ROOT_PATH.'/data');
define('WPT_DATA_WPATH', '/data');

// Locales
define('WPT_LOCALES', [
  'en' => 'Europe/London',
  'fr' => 'Europe/Paris',
]);
define('WPT_DEFAULT_LOCALE', 'en');

// Users groups types
define('WPT_GTYPES_DED', 1);
define('WPT_GTYPES_GEN', 2);

// Walls access rights
define('WPT_WRIGHTS_ADMIN', 1);
define('WPT_WRIGHTS_RW', 2);
define('WPT_WRIGHTS_RO', 3);

// Regexp for direct URL
// - a = deadline alert
// - c = comment notification
// - s = wall sharing notification
// - w = note assignation notification
define('WPT_DIRECTURL_REGEXP',
    '/unsubscribe|(a|c|s|w)(\d+)(p(\d+)(c(\d+))?)?$/');

// Blacklisted emails domains (with a pipe separator)
//define('WPT_BLACKLISTED_DOMAINS', 'outlook.fr|outlook.com|live.fr|live.com|hotmail.fr|hotmail.com|caramail.fr');

// Themes
define('WPT_THEMES', [
  'black',
  'default',
  'green',
  'orange',
  'purple',
  'red',
]);

// Postit colors
define('WPT_POSTIT_COLORS', [
  'blue' => '#9ef2ff',
  'gray' => '#c8d0d7',
  'green' => '#b3ffc4',
  'orange' => '#ffdd9f',
  'red' => '#ff9fa8',
  'yellow' => '#ffffc6',
]);
// Default postit color
define('WPT_POSTIT_COLOR_DEFAULT', 'yellow');

// Size of the Swoole volatile session tables.
define('WPT_SWOOLE_TABLE_SIZE', 1024);

// WebSocket server sections
define('WS_SERVER_SECTIONS', [
  'clients',
  'openedWalls',
  'activeWalls',
  'chatUsers',
]);

// Relationships lines defaults
// (the default color will depends on the theme)
define('WPT_PLUG_DEFAULTS', [
  'lineType' => 'solid',
  'lineSize' => 5,
  'linePath' => 'fluid',
]);

////////////// This whome section will be removed after deployment ////////////

//<WPTPROD-remove>

// Max upload file size in Mb
define('WPT_UPLOAD_MAX_SIZE', 20);
// Max size for wopits export/import file in Mb
define('WPT_IMPORT_UPLOAD_MAX_SIZE', 500);

// Max cells in a wall (for performance reasons)
define('WPT_MAX_CELLS', 400);

// Timeouts (seconds).
define('WPT_TIMEOUTS', [
  // Edition ("editable" plugin) without activity
  'edit' => 15,
  'network_connection' => 5,
]);

// Popups
define('WPT_POPUPS', [
  'login' => [
    'about',
    'createAccount',
    'info',
    'resetPassword',
    'settings',
  ],
  'index' => [
    'account',
    'confirm',
    'info',
    'postitUpdate',
    'settings',
  ],
]);

// Modules
define('WPT_MODULES', [
  'common' => [],
  // wopits settings
  'settings' => [],
  // Postit tags picker
  'tpick' => [
    'items' => [
      'ambulance',
      'balance-scale',
      'bell',
      'birthday-cake',
      'bullhorn',
      'certificate',
      'cubes',
      'donate',
      'fire',
      'fire-extinguisher',
      'life-ring',
      'lightbulb',
      'peace',
      'phone',
      'thumbs-down',
      'thumbs-up',
      'tools',
      'users',
    ]
  ],
  // User account
  'account' => [],
  // Wall cell
  'cell' => [],
  // Wall chat
  'chat' => [],
  // Postit color picker
  'cpick' => ['items' => WPT_POSTIT_COLORS],
  // Postit date picker
  'dpick' => [],
  // Make a field editable by user
  'editable' => [],
  // Global events
  'events' => [],
  // Wall postits filter
  'filters' => [],
  // Wall header
  'header' => [],
  // User login
  'login' => [],
  'main' => [],
  // Wall meta menu
  'mmenu' => [],
  // Walls opener
  'owall' => [],
  // Postit attachments
  'patt' => [],
  // Postit comments
  'pcomm' => [],
  // Relation plugs properties
  'plugprop' => [],
  // Wall postit
  'postit' => [],
  // Wall postits search
  'psearch' => [],
  // Postit workers
  'pwork' => [],
  // Postit slider
  'slider' => [],
  // Wall sharing popup
  'swall' => [],
  // User messages
  'umsg' => [],
  // Users search select
  'usearch' => [],
  // Wall menu
  'wmenu' => [],
  // Wall properties
  'wprop' => [],
]);

// JS node modules
define('WPT_JS_NODE_MODULES', [
  'jquery/dist/jquery.min.js',
  'jquery-ui-dist/jquery-ui.min.js',
  '@rwap/jquery-ui-touch-punch/jquery.ui.touch-punch.js',
  'bootstrap/dist/js/bootstrap.bundle.min.js',
  'vanderlee-colorpicker/jquery.colorpicker.js',
  'leader-line/leader-line.min.js',
]);

// JS local modules
define ('WPT_JS_LOCAL_MODULES', [
  'jquery.double-tap-wopits.js',
]);

//</WPTPROD-remove>
