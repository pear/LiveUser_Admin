<?php

require_once 'LiveUser/Admin.php';

$dsn = 'mysql://root:@localhost/liveuser_test';

$liveuserConfig = array(
    'login' => array(
        'force' => true,
        'function' => 'loginFunction',
    ),
    'authContainers' => array(
        0 => array(
            'type' => 'XML',
            'file' => 'Auth_XML.xml',
            'passwordEncryptionMode' => 'MD5'
        ),
    ),
    'permContainer' => array(
        'type'  => 'Simple',
        'stack' => array('MDB2' => array('dsn' => $dsn, 'prefix'     => 'liveuser_')),
    ),
);

$lu =& LiveUser_Admin::factory($liveuserConfig, 'de');
$lu->setAdminContainers();

$user = $lu->perm->getUser();
var_dump($user);

?>