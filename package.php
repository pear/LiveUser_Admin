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

$version = '0.3.3';

$notes = <<<EOT
- typo fix in phpdoc (bug #5054)
- removed space in the example1 directory name
- ensure that fields are properly aliased (bug #5059)
- added an aliased field to exampl1 so that we can better test liveuser_admin
- several CS fixes
- removed uncessary optional dependencies on XML_Tree and Crypt_RC4
- fixed issue in upate() handling in the storage of required fields
- supress errors due to deleting non existant tables in example1
- fixed bug in setFetchMode() calling in example1
- removed warning about extra_sql.sql in example1
- pass the storage config array by ref after all
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
$package->addDependency('MDB2',      '2.0.0beta4', 'ge',  'pkg', true);

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
