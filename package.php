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

$version = '0.2.0';

$notes = <<<EOT
- perm container now also has a default init() method that is called in the factory
- delete/update now dont require a filter in the id field
- delete/update now return the number of affected rows
- use LiveUser::cryptRC4() for rc4 handling
- added support to be able to query for single values, columns and rows next to
  fetching multi dimensional arrays ('select' key in params array of get methods)
- improved error handling in several places to not trigger on empty results
- sequences are now named after the table they belong to
- now requiring MDB2-2.0.0beta3
- if the "force-seq" option is now set to false in the storage config
  MDB2 will try to use autoincrement if supported by the RDBMS (you will need
  to add autoincrement to the id fields in the applications, areas, groups,
  rights, and perm_users tables yourself)
- example 1 now outputs all queries using an MDB2 debug handler
- Subgroups now work
- ImplyRights now work
- One can now remove groups recursively by passing recursive = true to 
  removeGroup in Perm Complex container (before it was hardcoded to true,
  now defaults to false)
- Tests for SubGroups and ImplyRights up and running
- getGroup and getRight now work in Perm Complex Container
- added DB and MDB permission backends
- Complex container is now fully implemented.
- addAreaAdmin and removeAreaAdmin where added to the Complex container
- Admin.php getUser was removed and searchUser was renamed to getUsers and with new params
- give each example a unique database name
- moved selectable tables into property so that they can be overwritten
- fixed autoinit handling in factoray (bug #3133)
- added missing PEAR error to error stack conversions
- return false if we previously ensured that the value is false anyways for clarity
- call setAdminAuthContainer() in updateUser() and removeUser() to ensure that
  the proper auth container is affected
- updated the file headers as per the RFC
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
    'lsmith', 'lead', 'Lukas Kahwe Smith', 'smith@backendmedia.com'
);
$package->addMaintainer(
    'dufuz', 'developer', 'Helgi Şormar', 'dufuz@php.net'
);

$package->addDependency('php',       '4.2.0',      'ge',  'php', false);
$package->addDependency('PEAR',      '1.3.1',      'ge',  'pkg', false);
$package->addDependency('LiveUser',  '0.14.0',     'ge',  'pkg', false);
$package->addDependency('Log',       '1.7.0',      'ge',  'pkg', true);
$package->addDependency('DB',        '1.6.0',      'ge',  'pkg', true);
$package->addDependency('MDB',       '1.1.4',      'ge',  'pkg', true);
$package->addDependency('MDB2',      '2.0.0beta3', 'ge',  'pkg', true);
$package->addDependency('XML_Tree',  false,        'has', 'pkg', true);
$package->addDependency('Crypt_RC4', false,        'has', 'pkg', true);

if (isset($_GET['make'])
    || (isset($_SERVER['argv'][1])
        && $_SERVER['argv'][1] == 'make')) {
    $result = $package->writePackageFile();
} else {
    $result = $package->debugPackageFile();
}

if (PEAR::isError($result)) {
    echo $result->getMessage();
}
