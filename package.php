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

$version = '0.3.8';

$notes = <<<EOT
- wrong parameter used in getUsers('auth', ..) (report by gregory)
- fixed usage of outdated getUsers() API in init()
- phpdoc fix in outputRightsConstants() (bug #7037)
- removed bogus parameter from phpdoc in getRights() in medium/complex container
- added support for selectable_tables in the param array in get*() methods
- fixed updating of implied right field in umimplyRight() (bug #7050)
- made stack property public
- remove artificial limitation that prevented groups to have multiple parents
- fixed PDO storage layer queryAll() method (bug #7213)
- expanded error handling in Log instance creation
- fixed outdated API call to getRights() in _getInheritedRights() (bug #7236)
- made translations columns wider for example1
- replace isset() with array_key_exists() where applicable
- added link to area admin area test to the menu in example1
- reworked getRights() and getGroups() API for recursive reads
  (related to bug #7241) *BC break*
  Set the filter parameters for the recursion explicitly. For getGroups() in the
  'subgroups', 'hierarchy' keys (note that hierarchy is now no longer specified
  by setting 'subgroups' => 'hierarchy'). For getRights() 'inherited', 'implied'
  and 'hierarchy' (note that hierarchy is now no longer specified by setting
  'implied' => 'hierarchy';).
- expanded outputRightsConstants() filtering
- changed the getUsers(), addUser() and updateUser() API to be more in line
  with the container APIs *BC break* (req #7025)
- added LiveUser_Admin_Storage::setSelectDefaultParams() to centralize default setting
- added selectable_tables property to auth backend
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
    'dufuz', 'lead', 'Helgi Þormar', 'dufuz@php.net'
);

$package->addDependency('php',       '4.2.0', 'ge',  'php', false);
$package->addDependency('PEAR',      '1.3.1', 'ge',  'pkg', false);
$package->addDependency('LiveUser','0.16.11', 'ge',  'pkg', false);
$package->addDependency('Log',       '1.7.0', 'ge',  'pkg', true);
$package->addDependency('DB',        '1.6.0', 'ge',  'pkg', true);
$package->addDependency('MDB',       '1.1.4', 'ge',  'pkg', true);
$package->addDependency('MDB2',      '2.0.0', 'ge',  'pkg', true);

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
