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
$options = array(
    'debug' => true,
    'debug_handler' => 'echoQuery',
);

function echoQuery(&$db, $scope, $message)
{
    Var_Dump::display($scope.': '.$message);
}

$db = MDB2::connect($dsn, $options);

if (PEAR::isError($db)) {
    echo $db->getMessage() . ' ' . $db->getUserInfo();
    die();
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
            'force'    => false,
        ),
        'logout' => array(
            'destroy'  => true,
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
            'alias' => array(),
            'storage' => array(
                'MDB2' => array(
                    'connection' => $db,
                    'prefix' => 'liveuser_',
                    'tables' => array(),
                    'fields' => array(),
#                    'force_seq' => false,
                ),
            ),
        ),
    );

$admin =& LiveUser_Admin::factory($conf);
$logconf = array('mode' => 0666, 'timeFormat' => '%X %x');
$logger = &Log::factory('file', 'liveuser_test.log', 'ident', $logconf);
$admin->addErrorLog($logger);
$admin->setAdminContainers();
