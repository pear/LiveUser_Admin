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

$version = '0.3.1';

$notes = <<<EOT
- php 4.4 reference assignment fixes
- dont filter out subgroups from the previous level in
  _getGroupsWithHierarchy() (bug #4860)
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
    'dufuz', 'lead', 'Helgi Şormar', 'dufuz@php.net'
);

$package->addDependency('php',       '4.2.0',      'ge',  'php', false);
$package->addDependency('PEAR',      '1.3.1',      'ge',  'pkg', false);
$package->addDependency('LiveUser',  '0.16.0',     'ge',  'pkg', false);
$package->addDependency('Log',       '1.7.0',      'ge',  'pkg', true);
$package->addDependency('DB',        '1.6.0',      'ge',  'pkg', true);
$package->addDependency('MDB',       '1.1.4',      'ge',  'pkg', true);
$package->addDependency('MDB2',      '2.0.0beta4', 'ge',  'pkg', true);
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
