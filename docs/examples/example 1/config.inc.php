<?php
error_reporting(E_ALL);
require_once 'MDB2.php';
require_once 'LiveUser/Admin.php';
// Please configure the following file according to your environment

$GLOBALS['_LIVEUSER_DEBUG'] = true;

$db_user = 'root';
$db_pass = '';
$db_host = 'localhost';
$db_name = 'liveuser_test';

$dsn = "mysql://$db_user:$db_pass@$db_host/$db_name";

$db = MDB2::connect($dsn);

if (PEAR::isError($db)) {
    echo $db->getMessage() . ' ' . $db->getUserInfo();
}

$db->setFetchMode(MDB2_FETCHMODE_ASSOC);

$conf =
    array(
        'autoInit' => false,
        'session'  => array(
            'name'     => 'PHPSESSION',
            'varname'  => 'ludata'
        ),
        'login' => array(
            'method'   => 'post',
            'username' => 'handle',
            'password' => 'passwd',
            'force'    => false,
            'function' => '',
            'remember' => 'rememberMe'
        ),
        'logout' => array(
            'trigger'  => 'logout',
            'redirect' => '?',
            'destroy'  => true,
            'method' => 'get',
            'function' => ''
        ),
        'authContainers' => array(
            array(
                'type'          => 'DB',
                'name'          => 'DB_Local',
                'loginTimeout'  => 0,
                'expireTime'    => 3600,
                'idleTime'      => 1800,
                'dsn'           => $dsn,
                'allowDuplicateHandles' => 0,
                'authTable'     => 'liveuser_users',
                    'authTableCols' => array(
                        'required' => array(
                            'auth_user_id' => array('type' => 'text',   'name' => 'auth_user_id'),
                            'handle'       => array('type' => 'text',   'name' => 'handle'),
                            'passwd'       => array('type' => 'text',   'name' => 'passwd'),
                        ),
                        'optional' => array(
                            'is_active'      => array('type' => 'boolean', 'name' => 'is_active'),
                            'lastlogin'      => array('type' => 'timestamp', 'name' => 'lastlogin'),
                            'owner_user_id'  => array('type' => 'integer',   'name' => 'owner_user_id'),
                            'owner_group_id' => array('type' => 'integer',   'name' => 'owner_group_id')
                        ),
                        'custom' => array (
                            'name' => array('type' => 'text',    'name' => 'name'),
                            'email'      => array('type' => 'text',    'name' => 'email'),
                        )
                    )
            )
        ),
        'permContainer' => array(
            'type'  => 'Medium',
            'alias' => array(
                'perm_user_id' => null,
                'auth_user_id' => null,
                'auth_container_name' => null,
                'perm_type' => null,
                'right_id' => null,
                'right_level' => null,
                'area_id' => null,
                'application_id' => null,
                'right_define_name' => null,
                'area_define_name' => null,
                'application_define_name' => null,
                'section_id' => null,
                'section_type' => null,
                'name' => null,
                'description' => null,
                'group_id' => null,
                'group_type' => null,
                'group_define_name' => null,
                'is_active' => null,
                'owner_user_id' => null,
                'owner_group_id' => null,
                'implied_right_id' => null,
            ),
            'stack' => array(
                'MDB2' => array(
                    'dsn' => $dsn,
                    'prefix' => 'liveuser_',
                    'fields' => array(
                        'perm_user_id' => 'integer',
                        'auth_user_id' => 'integer',
                        'auth_container_name' => 'text',
                        'perm_type' => 'integer',
                        'right_id' => 'integer',
                        'right_level' => 'integer',
                        'area_id' => 'integer',
                        'application_id' => 'integer',
                        'right_define_name' => 'text',
                        'area_define_name' => 'text',
                        'application_define_name' => 'text',
                        'section_id' => 'integer',
                        'section_type' => 'integer',
                        'name' => 'text',
                        'description' => 'text',
                        'group_id' => 'integer',
                        'group_type' => 'integer',
                        'group_define_name' => 'text',
                        'is_active' => 'boolean',
                        'owner_user_id' => 'integer',
                        'owner_group_id' => 'integer',
                        'implied_right_id' => 'integer',
                    ),
                    'tables' => array(
                        'perm_users' => array(
                            'fields' => array(
                                'perm_user_id' => 'seq',
                                'auth_user_id' => true,
                                'auth_container_name' => true,
                                'perm_type' => false,
                             ),
                            'joins' => array(
                                'userrights' => 'perm_user_id',
                                'groupusers' => 'perm_user_id',
                            ),
                            'id' => 'perm_user_id',
                        ),
                        'userrights' => array(
                            'fields' => array(
                                'perm_user_id' => true,
                                'right_id' => true,
                                'right_level' => false,
                            ),
                            'joins' => array(
                                'perm_users' => 'perm_user_id',
                                'rights' => 'right_id',
                            ),
                        ),
                        'rights' => array(
                            'fields' => array(
                                'right_id' => 'seq',
                                'area_id' => false,
                                'right_define_name' => false,
                            ),
                            'joins' => array(
                                'areas' => 'area_id',
                                'userrights' => 'right_id',
                                'grouprights' => 'right_id',
                                'rights_implied' => array(
                                    'right_id' => 'right_id',
                                    'right_id' => 'implied_right_id',
                                ),
                                'translations' => array(
                                    'right_id' => 'section_id',
                                    LIVEUSER_SECTION_RIGHT => 'section_type',
                                ),
                            ),
                            'id' => 'right_id',
                        ),
                        'rights_implied' => array(
                            'fields' => array(
                                'right_id' => true,
                                'implied_right_id' => true,
                            ),
                            'joins' => array(
                                'rights' => array(
                                    'right_id' => 'right_id',
                                    'implied_right_id' => 'right_id',
                                ),
                            ),
                        ),
                        'translations' => array(
                            'fields' => array(
                                'section_id' => true,
                                'section_type' => true,
                                'name' => false,
                                'description' => false,
                            ),
                            'joins' => array(
                                'rights' => array(
                                    'section_id' => 'right_id',
                                    'section_type' => LIVEUSER_SECTION_RIGHT,
                                ),
                                'areas' => array(
                                    'section_id' => 'area_id',
                                    'section_type' => LIVEUSER_SECTION_AREA,
                                ),
                                'applications' => array(
                                     'section_id' => 'application_id',
                                     'section_type' => LIVEUSER_SECTION_APPLICATION,
                                ),
                                'groups' => array(
                                    'section_id' => 'group_id',
                                    'section_type' => LIVEUSER_SECTION_GROUP,
                                ),
                            ),
                        ),
                        'areas' => array(
                            'fields' => array(
                                'area_id' => 'seq',
                                'application_id' => false,
                                'area_define_name' => false,
                            ),
                            'joins' => array(
                                'rights' => 'area_id',
                                'applications' => 'application_id',
                                'translations' => array(
                                    'area_id' => 'section_id',
                                    LIVEUSER_SECTION_AREA => 'section_type',
                                ),
                            ),
                            'id' => 'area_id',
                        ),
                        'applications' => array(
                            'fields' => array(
                                'application_id' => 'seq',
                                'application_define_name' => false,
                            ),
                            'joins' => array(
                                'areas' => 'application_id',
                                'translations' => array(
                                    'application_id' => 'section_id',
                                    LIVEUSER_SECTION_APPLICATION => 'section_type',
                                ),
                            ),
                            'id' => 'application_id',
                        ),
                        'groups' => array(
                            'fields' => array(
                                'group_id' => 'seq',
                                'group_type' => false,
                                'group_define_name' => false,
                                'is_active' => false,
                                'owner_user_id' => false,
                                'owner_group_id' => false,
                            ),
                            'joins' => array(
                                'groupusers' => 'group_id',
                                'grouprights' => 'group_id',
                                'translations' => array(
                                    'group_id' => 'section_id',
                                    LIVEUSER_SECTION_GROUP => 'section_type',
                                ),
                            ),
                            'id' => 'group_id',
                        ),
                        'groupusers' => array(
                            'fields' => array(
                                'perm_user_id' => true,
                                'group_id' => true,
                            ),
                            'joins' => array(
                                'groups' => 'group_id',
                                'perm_users' => 'perm_user_id',
                            ),
                        ),
                        'grouprights' => array(
                            'fields' => array(
                                'group_id' => true,
                                'right_id' => true,
                                'right_level' => false,
                            ),
                            'joins' => array(
                                'rights' => 'right_id',
                                'groups' => 'group_id',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    );

$admin =& LiveUser_Admin::factory($conf);
$logconf = array('mode' => 0666, 'timeFormat' => '%X %x');
$logger = &Log::factory('file', 'liveuser_test.log', 'ident', $logconf);
$admin->addErrorLog($logger);
$admin->setAdminContainers();
