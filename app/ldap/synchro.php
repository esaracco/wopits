#!/usr/bin/php
<?php

// This script synchronize LDAP users to wopits database.
// -> Execute it with a user who has the right to write into the wopits
//    directory "data/".

require_once(__DIR__.'/../config.php');

use Wopits\{Ldap, User};

if (!WPT_USE_LDAP) {
  exit("ERROR wopits has not been configured to manage LDAP ".
       "authentication!\n\n");
}

// Check that the current user is OK for LDAP to wopits synchronization.
$flag = WPT_DATA_SPATH.'/.'.time();
if (!@mkdir($flag, 02770)) {
  exit("ERROR This script must be executed on the wopits server by a ".
       "user who has the right to write into the wopits ".
       "directory `".WPT_DATA_SPATH."`!\n\n");
}
rmdir($flag);

// Connect to the LDAP server
if (!($Ldap = new Ldap())->connect()) {
  exit("ERROR LDAP connection failed!\n\n");
}

// Get all LDAP users
$ldapUsers = $Ldap->getUsers(true);
if (empty($ldapUsers)) {
  exit("WARNING Nothing to synchronize!\n\n");
}

$User = new User();

// Import users (update if exists, create if not)
foreach ($ldapUsers as $item) {
  $data = $User->createUpdateLdapUser([
    'fromScript' => true,
    'username' => $item['uid'],
    'password' => '',
    'mail' => $item['mail'],
    'cn' => $item['cn'],
   ]);

  if (!empty($data['error_msg'])) {
    echo "ERROR {$data['error_msg']}\n\n";
  }
}

?>
