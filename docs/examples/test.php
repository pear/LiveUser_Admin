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
        'type'  => 'Medium',
        'stack' => array('MDB2' => array('dsn' => $dsn, 'prefix'     => 'liveuser_')),
    ),
);

$lu =& LiveUser_Admin::factory($liveuserConfig);
$lu->setAdminContainers();

$params_rights = array(
    'filters' => array(
        'area_id' => 1,
    ),
);

$params_users = array(
    'fields' => array(
        'perm_user_id',
        'perm_type',
        'auth_container_name',
    ),
    'with' => array(
        'perm_user_id' => array(
            'fields' => array(
                'right_id',
                'right_level',
                'name',
            ),
        ),
    ),
    'filters' => array(
        'perm_type' => 1,
        'auth_container_name' => '0',
    ),
    'orders' => array(
        'perm_type' => 'DESC',
        'auth_user_id' => 'ASC',
    ),
    'limit' => 10,
    'offset' => 0,
);

$params_applications = array();

$params_areas = array();

$params_groups = array('filters' => array('perm_user_id' => '1'));

echo '<pre>';
echo 'input';
var_dump($params_rights);
echo '<hr>';
echo 'output';
#var_dump($lu->perm->getRights($params_rights));
echo '<hr>';
echo 'underlying query:';
var_dump($lu->perm->_storage->dbc->last_query);
echo '<hr>';
echo 'input';
var_dump($params_users);
echo '<hr>';
echo 'output';
#var_dump($lu->perm->getUsers($params_users));
echo '<hr>';
echo 'underlying query:';
var_dump($lu->perm->_storage->dbc->last_query);

echo '<hr>';
echo 'input';
var_dump($params_applications);
echo '<hr>';
echo 'output';
var_dump($lu->perm->getApplications($params_applications));
echo '<hr>';
echo 'underlying query:';
var_dump($lu->perm->_storage->dbc->last_query);

echo '<hr>';
echo 'input';
var_dump($params_areas);
echo '<hr>';
echo 'output';
var_dump($lu->perm->getAreas($params_areas));
echo '<hr>';
echo 'underlying query:';
var_dump($lu->perm->_storage->dbc->last_query);

echo '<hr>';
echo 'input';
var_dump($params_groups);
echo '<hr>';
echo 'output';
var_dump($lu->perm->getGroups($params_groups));
echo '<hr>';
echo 'underlying query:';
var_dump($lu->perm->_storage->dbc->last_query);
echo '</pre>';
?>
