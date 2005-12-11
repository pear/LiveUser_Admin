<?php
/**
 * Script to generate package.xml file
 *
 * Taken from PEAR::Log, thanks Jon ;)
 *
 * $Id$
 */
require_once 'PEAR/PackageFileManager.php';
require_once 'Console/Getopt.php';

$version = '0.3.6';

$notes = <<<EOT
- dont raise an error when no user is found in getUsers() helper method (bug #5690)
- fixed bug in handling of _checkExplicitTable() return value in findTables() (bug #5706)
- tweaked error messages for failed factory method calls
- renamed query() to exec() to match the MDB2 RC1 API
- use exec() instead of query()
- handle database structure merging inside the Storage class
  (previously handled in the auth/perm backend drivers)
- improved sanity checking in insert and update when values for undefined fields are passed
- do nothing in update() if an empty data array is passed
- required fields may not be unset, but may be empty strings
  (corrected overly strict check in update() method)
- owner_*_id is no longer necessary for the tests
- leave owner_user_id management to the developer
- tweaked errors messages in insert() and update()
- in createJoinFilter first work through all the direct matches before recursing
- typo in loadModule call in the MDB2 backend (bug #6092)
- removed loginTimeout feature (disable lastlogin if you are concerned about
  the cost of updating the lastlogin time)
- tons of phpdoc and whitespace fixes and additions
EOT;

$description = <<<EOT
  LiveUser_Admin is meant to be used with the LiveUser package.
  It is composed of all the classes necessary to administrate
  data used by LiveUser.

  You'll be able to add/edit/delete/get things like:
  * Rights
  * Users
  * Groups
  * Areas
  * Applications
  * Subgroups
  * ImpliedRights

  And all other entities within LiveUser.

  At the moment we support the following storage containers:
  * DB
  * MDB
  * MDB2

  But it takes no time to write up your own storage container,
  so if you like to use native mysql functions straight, then it's possible
  to do so in under a hour!
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(array(
    'package'           => 'LiveUser_Admin',
    'summary'           => 'User authentication and permission management framework',
    'description'       => $description,
    'version'           => $version,
    'state'             => 'beta',
    'license'           => 'LGPL',
    'filelistgenerator' => 'cvs',
    'ignore'            => array('package.php', 'package.xml'),
    'notes'             => $notes,
    'changelogoldtonew' => false,
    'simpleoutput'      => true,
    'baseinstalldir'    => '/LiveUser/Admin',
    'packagedirectory'  => './',
    'installexceptions' => array(
        'Admin.php'            => '/LiveUser',
    ),
    'exceptions'         => array(
        'lgpl.txt' => 'doc',
    ),
    'dir_roles'         => array('sql'               => 'data',
                                 'docs'              => 'doc',
                                 'scripts'           => 'script')
));

if (PEAR::isError($result)) {
    echo $result->getMessage();
}

$package->addMaintainer(
    'mw21st', 'lead', 'Markus Wolff', 'mw21st@php.net'
);
$package->addMaintainer(
    'arnaud', 'lead', 'Arnaud Limbourg', 'arnaud@php.net'
);
$package->addMaintainer(
    'lsmith', 'lead', 'Lukas Kahwe Smith', 'smith@pooteeweet.org'
);
$package->addMaintainer(
    'dufuz', 'lead', 'Helgi Şormar', 'dufuz@php.net'
);

$package->addDependency('php',       '4.2.0',      'ge',  'php', false);
$package->addDependency('PEAR',      '1.3.1',      'ge',  'pkg', false);
$package->addDependency('LiveUser',  '0.16.0',     'ge',  'pkg', false);
$package->addDependency('Log',       '1.7.0',      'ge',  'pkg', true);
$package->addDependency('DB',        '1.6.0',      'ge',  'pkg', true);
$package->addDependency('MDB',       '1.1.4',      'ge',  'pkg', true);
$package->addDependency('MDB2',      '2.0.0RC1', 'ge',  'pkg', true);

if (array_key_exists('make', $_GET)
    || (isset($_SERVER['argv'][1])
        && $_SERVER['argv'][1] == 'make')) {
    $result = $package->writePackageFile();
} else {
    $result = $package->debugPackageFile();
}

if (PEAR::isError($result)) {
    echo $result->getMessage();
}
