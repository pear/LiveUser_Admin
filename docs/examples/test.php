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

$params = array(
    'fields' => array(
        'perm_user_id',
        'right_define_name',
        'right_id',
        'right_level',
        'name',
    ),
    'filters' => array(
        'perm_type' => 1,
        'auth_container_name' => '0',
    ),
    'orders' => array(
        'perm_type' => 'DESC',
        'auth_user_id' => 'ASC',
    ),
    'rekey' => true,
    'limit' => 10,
    'offset' => 0,
);

echo 'input';
var_dump($params);
echo '<hr>';
echo 'output';
var_dump($lu->perm->getUser($params));
echo '<hr>';
echo 'underlying query:';
var_dump($lu->perm->_storage->dbc->last_query);

?>