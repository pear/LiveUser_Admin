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

$version = '0.3.7';

$notes = <<<EOT
- fix "No rights for a user if the user only has inherited rights" (bug #6374)
- do not overwrite all filters in _get*() helper methods
- minor issue with 'alias' position in the config array in example1
- make sure that tables required as intermediate join steps are listed in the from
- add depth parameter to createJoinFilter (may be used to determine shortest join path eventually)
- fixed detection if list of tables has been reduced or not
- do not push an error on the stack for a possible recursion because it may just
  be one possible path we are evaluating
- added "by_group" optional parameter to params getRights() which determines if
  the userrights table should be used or rather the grouprights and groupupsers tables
- incorrect handling of filters inside unimplyRights() (bug #6592)
- renamed "connection" config option to "dbc" *BC BREAK*
- cleaned up and unified init() in the storage classes
- added support for '*' in fields list as an alias to fetch all fields in the root table
- made LiveUser_Admin::getUsers() API as flexible as in the containers *BC BREAK*
- fixed serious issue in join filter handling that caused join filters to be ignored
- removed allowDuplicateHandles and allowEmptyPasswords options, they are now
  handled through the table definition in the given Globals.php (overwriteable
  via the config array) *BC BREAK*
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
