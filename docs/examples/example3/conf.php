<?php
// BC hack
if (!defined('PATH_SEPARATOR')) {
    if (defined('DIRECTORY_SEPARATOR') && DIRECTORY_SEPARATOR == "\\") {
        define('PATH_SEPARATOR', ';');
    } else {
        define('PATH_SEPARATOR', ':');
    }
}

error_reporting(E_ALL);

// right definitions
define('READ_TESTS', 1);
define('WRITE_TESTS', 2);
define('ACCESS', 3);
define('LAUNCH_ATOMIC_BOMB', 4);
define('FLY_ALIEN_SPACE_CRAFT', 5);
define('MAKE_COFFEE', 6);
define('DRINK_COFFEE', 7);

// set this to the path in which the directory for liveuser resides
// more remove the following two lines to test LiveUser in the standard
// PEAR directory
//$path_to_liveuser_dir = './pear/'.PATH_SEPARATOR;
//ini_set('include_path', $path_to_liveuser_dir.ini_get('include_path'));

// Data Source Name (DSN)
$dsn = 'mysql://root@localhost/liveuser_admin_test_example3';

$liveuserConfig = array(
    'session'           => array('name' => 'PHPSESSID','varname' => 'loginInfo'),
    'logout'            => array('destroy'  => true),
    'cookie'            => array(
        'name' => 'loginInfo',
        'path' => null,
        'domain' => null,
        'secure' => false,
        'lifetime' => 30,
        'secret' => 'mysecretkey',
        'savedir' => '.',
    ),
    'authContainers'    => array(
        'DB' => array(
            'type'          => 'DB',
            'loginTimeout' => 0,
            'expireTime'   => 0,
            'idleTime'     => 0,
            'allowDuplicateHandles'  => 1,
            'passwordEncryptionMode' => 'PLAIN',
            'storage' => array(
                'dsn' => $dsn,
                'alias' => array(
                    'lastlogin' => 'lastlogin',
                    'is_active' => 'is_active',
                ),
                'fields' => array(
                    'lastlogin' => 'timestamp',
                    'is_active' => 'boolean',
                ),
                'tables' => array(
                    'users' => array(
                        'fields' => array(
                            'lastlogin' => false,
                            'is_active' => false,
                        ),
                    ),
                ),
            )
        )
    ),
    'permContainer' => array(
        'type'  => 'Complex',
        'alias' => array(),
        'storage' => array(
            'MDB2' => array(
                'dsn' => $dsn,
                'prefix' => 'liveuser_',
                'tables' => array(),
                'fields' => array(),
            ),
        ),
    ),
);

// Get LiveUser class definition
require_once 'LiveUser.php';

// The error handling stuff is not needed and used only for debugging
// while LiveUser is not yet mature
PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'eHandler');

function eHandler($errObj)
{
    echo('<hr /><span style="color: red;">' . $errObj->getMessage() . ':<br />' . $errObj->getUserInfo() . '</span><hr />');
    $debug_backtrace = debug_backtrace();
    array_shift($debug_backtrace);
    $message= 'Debug backtrace:'."\n";

    foreach ($debug_backtrace as $trace_item) {
        $message.= "\t" . '    @ ';
        if (isset($trace_item['file'])) {
            $message.= basename($trace_item['file']) . ':' . $trace_item['line'];
        } else {
            $message.= '- PHP inner-code - ';
        }
        $message.= ' -- ';
        if (isset($trace_item['class'])) {
            $message.= $trace_item['class'] . $trace_item['type'];
        }
        $message.= $trace_item['function'];

        if (isset($trace_item['args']) && is_array($trace_item['args'])) {
            $message.= '('.@implode(', ', $trace_item['args']).')';
        } else {
            $message.= '()';
        }
        $message.= "\n";
    }
    echo "<pre>$message</pre>";
}

// Create new LiveUser object
$LU =& LiveUser::factory($liveuserConfig);

$handle = (isset($_REQUEST['handle'])) ? $_REQUEST['handle'] : null;
$passwd = (isset($_REQUEST['passwd'])) ? $_REQUEST['passwd'] : null;
$logout = (isset($_REQUEST['logout'])) ? $_REQUEST['logout'] : false;
$remember = (isset($_REQUEST['rememberMe'])) ? $_REQUEST['rememberMe'] : false;

if (!$LU->init($handle, $password, $logout, $remember)) {
    var_dump($LU->getErrors());
    die();
}

require_once 'LiveUser/Admin.php';

$luadmin =& LiveUser_Admin::factory($liveuserConfig);
$luadmin->setAdminContainers();

$language_selected = isset($_GET['language']) ? $_GET['language'] : 'de';
