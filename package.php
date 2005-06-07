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

$version = '0.3.0';

$notes = <<<EOT
- added _call() overloading method for php5 users in LiveUser_Admin class
- dont require a conf array for all but the first call of singleton()

storage
- delete() now uses findTable() to ensure that only defined table with the
  proper fields are being used
- findTable() now only prefixes fields if necessary
- added ability to prefix explicit tables in findTables()
- no longer use "ids" in insert so we can remove this information from the
  Globals.php file in the client
- added support for table name aliasing (fairly untested)

authentication
- typo fix (bug #4109)
- typo fix (bug #4173)
- moved to admin storage class
- tweaked disconnect to only disconnect when a new connection was made

permission
- typo fix: hierachy -> hierarchy (bug #4150)
- improved the "with" support (fixing bug #3245)
- tweaked disconnect to only disconnect when a new connection was made

examples
- examples were converted to use MDB2_Schema. See the demodata.php script found
  in the client part (http://cvs.php.net/co.php/pear/LiveUser/docs/examples/demodata.php)
- removed the test.php script since this code is outdated and serves no purpose any longer
- updated examples to use the new auth config layout due to using admin storage
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
    'dufuz', 'lead', 'Helgi Þormar', 'dufuz@php.net'
);

$package->addDependency('php',       '4.2.0',      'ge',  'php', false);
$package->addDependency('PEAR',      '1.3.1',      'ge',  'pkg', false);
$package->addDependency('LiveUser',  '0.15.0',     'ge',  'pkg', false);
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
