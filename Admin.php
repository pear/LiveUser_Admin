<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * A framework for authentication and authorization in PHP applications
 *
 * LiveUser_Admin is meant to be used with the LiveUser package.
 * It is composed of all the classes necessary to administrate
 * data used by LiveUser.
 * 
 * You'll be able to add/edit/delete/get things like:
 * * Rights
 * * Users
 * * Groups
 * * Areas
 * * Applications
 * * Subgroups
 * * ImpliedRights
 * 
 * And all other entities within LiveUser.
 * 
 * At the moment we support the following storage containers:
 * * DB
 * * MDB
 * * MDB2
 * 
 * But it takes no time to write up your own storage container,
 * so if you like to use native mysql functions straight, then it's possible
 * to do so in under a hour!
 *
 * PHP version 4 and 5 
 *
 * LICENSE: This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public 
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston,
 * MA  02111-1307  USA 
 *
 *
 * @category authentication
 * @package  LiveUser_Admin
 * @author  Markus Wolff <wolff@21st.de>
 * @author Helgi Þormar Þorbjörnsson <dufuz@php.net>
 * @author  Lukas Smith <smith@backendmedia.com>
 * @author Arnaud Limbourg <arnaud@php.net>
 * @author  Christian Dickmann <dickmann@php.net>
 * @author  Matt Scifo <mscifo@php.net>
 * @author  Bjoern Kraus <krausbn@php.net>
 * @copyright 2002-2005 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version CVS: $Id$
 * @link http://pear.php.net/LiveUser_Admin
 */

require_once 'LiveUser.php';

/**#@+
 * Error related constants definition
 *
 * @var integer
 */
define('LIVEUSER_ADMIN_ERROR',                  -1);
define('LIVEUSER_ADMIN_ERROR_FILTER',           -2);
define('LIVEUSER_ADMIN_ERROR_DATA',             -3);
define('LIVEUSER_ADMIN_ERROR_QUERY_BUILDER',    -4);
define('LIVEUSER_ADMIN_ERROR_ALREADY_ASSIGNED', -5);
define('LIVEUSER_ADMIN_ERROR_NOT_SUPPORTED',    -6);
/**#@-*/

/**
 * Attempt at a unified admin class
 *
 * Simple usage:
 *
 * <code>
 * $admin = new LiveUser_Admin::factory($conf);
 * $filters = array(
 *     'perm_user_id' => '3'
 * );
 * $found = $admin->getUsers($filters);
 *
 * if ($found) {
 *  var_dump($admin->perm->getRights());
 * }
 * </code>
 *
 * @see     LiveUser::factory()
 *
 * @category authentication
 * @package  LiveUser_Admin
 * @author  Lukas Smith <smith@backendmedia.com>
 * @author  Arnaud Limbourg <arnaud@php.net>
 * @author Helgi Þormar Þorbjörnsson <dufuz@php.net>
 * @copyright 2002-2005 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version Release: @package_version@
 * @link http://pear.php.net/LiveUser_Admin
 */
class LiveUser_Admin
{

     /**
      * Name of the current selected auth container
      *
      * @var    string
      * @access public
      */
     var $authContainerName;

    /**
     * Array containing the auth objects.
     *
     * @var    array
     * @access private
     */
    var $_authContainers = array();

    /**
     * Admin perm object
     *
     * @var    object
     * @access public
     */
    var $perm = null;

    /**
     * Auth admin object
     *
     * @var    object
     * @access public
     */
    var $auth = null;

    /**
     * Configuration array
     *
     * @var    array
     * @access private
     */
     var $_conf = null;

    /**
     * Error codes to message mapping array
     *
     * @var    array
     * @access private
     */
    var $_errorMessages = array(
        LIVEUSER_ADMIN_ERROR                  => 'An error occurred %msg%',
        LIVEUSER_ADMIN_ERROR_FILTER           => 'There\'s something obscure with the filter array, key %key%',
        LIVEUSER_ADMIN_ERROR_DATA             => 'There\'s something obscure with the data array, key %key%',
        LIVEUSER_ADMIN_ERROR_QUERY_BUILDER    => 'Couldn\'t create the query, reason: %reason%',
        LIVEUSER_ADMIN_ERROR_ALREADY_ASSIGNED => 'That given %field1% has already been assigned to %field2%',
        LIVEUSER_ADMIN_ERROR_NOT_SUPPORTED    => 'This method is not supported'
    );

    /**
     * PEAR::Log object
     * used for error logging by ErrorStack
     *
     *
     * @var    Log
     * @access private
     */
    var $_log = null;

    function LiveUser_Admin()
    {
        $this->_stack = &PEAR_ErrorStack::singleton('LiveUser_Admin');

        if ($GLOBALS['_LIVEUSER_DEBUG']) {
            if (!is_object($this->_log)) {
                $this->loadPEARLog();
            }
            $winlog = &Log::factory('win', 'LiveUser_Admin');
            $this->_log->addChild($winlog);
        }

        $this->_stack->setErrorMessageTemplate($this->_errorMessages);
    }

    /**
     * This method lazy loads PEAR::Log
     *
     * @return void
     *
     * @access protected
     */
    function loadPEARLog()
    {
        require_once 'Log.php';
        $this->_log = &Log::factory('composite');
        $this->_stack->setLogger($this->_log);
    }

    /**
     * Add error logger for use by Errorstack.
     *
     * Be aware that if you need add a log
     * at the beginning of your code if you
     * want it to be effective. A log will only
     * be taken into account after it's added.
     *
     * Sample usage:
     * <code>
     * $lu_object = &LiveUser_Admin::singleton($conf);
     * $logger = &Log::factory('mail', 'bug@example.com',
     *      'myapp_debug_mail_log', array('from' => 'application_bug@example.com'));
     * $lu_object->addErrorLog($logger);
     * </code>
     *
     * @param  Log &$log logger instance
     * @return boolean true on success or false on failure
     *
     * @access public
     */
    function addErrorLog(&$log)
    {
        if (!is_object($this->_log)) {
            $this->loadPEARLog();
        }
        return $this->_log->addChild($log);
    }

    /**
     *
     * @param array $conf configuration array
     * @return object
     *
     * @access public
     * @see setAdminContainers
     */
    function &factory($conf)
    {
        $obj = &new LiveUser_Admin;

        if (is_array($conf) && !empty($conf)) {
            $obj->_conf = $conf;
            if (isset($obj->_conf['autoInit']) && $obj->_conf['autoInit']) {
                $obj->setAdminContainers();
            }
        }

        return $obj;
    }

    /**
     *
     * @param array $conf configuration array
     * @return object
     *
     * @access public
     * @see factory
     */
    function &singleton($conf)
    {
        static $instance;

        if (!isset($instance)) {
            $obj = &LiveUser_Admin::factory($conf);
            $instance =& $obj;
        }

        return $instance;
    }

    /**
     * Merges the current configuration array with configuration array pases
     * along with the method call.
     *
     * @param  array $conf configuration array
     * @return boolean true upon success, false otherwise
     *
     * @access public
     */
    function setConfArray($conf)
    {
        if (!is_array($conf)) {
            $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Missing configuration array'));
            return false;
        }

        $this->_conf = LiveUser::arrayMergeClobber($this->_conf, $conf);
        return true;
    }

    /**
     * Sets the current auth container to the one with the given auth container name
     *
     * Upon success it will return true. You can then
     * access the auth backend container by using the
     * auth property of this class.
     *
     * e.g.: $admin->auth->addUser();
     *
     * @param  string $authName  auth container name
     * @return boolean true upon success, false otherwise
     *
     * @access public
     */
    function &setAdminAuthContainer($authName)
    {
        if (!isset($this->_authContainers[$authName])
            || !is_object($this->_authContainers[$authName])
        ) {
            if (!isset($this->_conf['authContainers'][$authName])) {
                $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception',
                    array('msg' => 'Could not create auth container instance'));
                return false;
            }
            $auth = &LiveUser::authFactory(
                $this->_conf['authContainers'][$authName],
                $authName,
                'LiveUser_Admin_'
            );
            if ($auth === false) {
                $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception',
                    array('msg' => 'Could not create auth container instance'));
                return false;
            }
            $this->_authContainers[$authName] = &$auth;
        }
        $this->authContainerName = $authName;
        $this->auth = &$this->_authContainers[$authName];
        return $this->auth;
    }

    /**
     * Sets the perm container
     *
     * Upon success it will return true. You can then
     * access the perm backend container by using the
     * perm properties of this class.
     *
     * e.g.: $admin->perm->addUser();
     *
     * @return boolean true upon success, false otherwise
     *
     * @access public
     */
    function &setAdminPermContainer()
    {
        if (!isset($this->_conf['permContainer'])) {
            $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Could not create perm container instance'));
            return false;
        }

        $this->perm = &LiveUser::permFactory(
            $this->_conf['permContainer'],
            'LiveUser_Admin_'
        );

        return $this->perm;
    }

    /**
     * Tries to find a user in any of the auth container.
     *
     * Upon success it will return true. You can then
     * access the backend container by using the auth
     * and perm properties of this class.
     *
     * e.g.: $admin->perm->updateAuthUserId();
     *
     * @param  mixed $authId  user auth id
     * @param  string $authName  auth container name
     * @return boolean true upon success, false otherwise
     *
     * @access public
     */
    function setAdminContainers($authId = null, $authName = null)
    {
        if (!is_array($this->_conf)) {
            $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Missing configuration array'));
            return false;
        }

        if (is_null($authName)) {
            if (is_null($authId)) {
                reset($this->_conf['authContainers']);
                $authName = key($this->_conf['authContainers']);
            } else {
                foreach ($this->_conf['authContainers'] as $key => $value) {
                    if (!isset($this->_authContainers[$key]) ||
                        !is_object($this->_authContainers[$key])
                    ) {
                        $this->_authContainers[$key] = &LiveUser::authFactory(
                            $value,
                            $key,
                            'LiveUser_Admin_'
                        );
                    }

                    if (!is_null($authId)) {
                        $match = $this->_authContainers[$key]->getUsers(
                            array('auth_user_id' => $authId)
                        );
                        if (is_array($match) && sizeof($match) > 0) {
                            $authName = $key;
                            break;
                        }
                    }
                }
            }
        }

        if (!isset($authName)) {
            $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Could not determine what auth container to use'));
            return false;
        }

        if (!$this->setAdminAuthContainer($authName)) {
            return false;
        }

        if (!isset($this->perm) || !is_object($this->perm)) {
            if (!$this->setAdminPermContainer()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Tries to add a user to both containers.
     *
     * If the optional $id parameter is passed it will be used
     * for both containers.
     *
     * In any case the auth and perm id will be equal when using this method.
     *
     * If this behaviour doesn't suit your needs please consider
     * using directly the concerned method. This method is just
     * implement to simplify things a bit and should satisfy most
     * user needs.
     *
     *  Note type is optional for DB, thus it's needed for MDB and MDB2,
     *  we recommend that you use type even though you use DB, so if you change to MDB[2],
     *  it will be no problem for you.
     *  usage example for addUser:
     * <code>
     *       $optional = array('is_active' => true);
     *       $user_id = $admin->addUser('johndoe', 'dummypass', $optional);
     *  </code>
     *
     * @param  string $handle  user handle (username)
     * @param  string $password user password
     * @param  array $optionalFields  values for the optional fields
     * @param  array $customFields  values for the custom fields
     * @param  int  $authId  Auth ID
     * @param  integer $type permission user type
     * @return mixed   userid or false
     *
     * @access public
     */
    function addUser($handle, $password, $optionalFields = array(),
        $customFields = array(), $authId = null, $type = LIVEUSER_USER_TYPE_ID)
    {
        if (!is_object($this->auth) || !is_object($this->perm)) {
            $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Perm and/or Auth container not set.'));
            return false;
        }

        $authId = $this->auth->addUser($handle, $password, $optionalFields,
            $customFields, $authId);

        if (!$authId) {
            return false;
        }

        $data = array(
            'auth_user_id' => $authId,
            'auth_container_name' => $this->authContainerName,
            'perm_type' => $type
        );
        return $this->perm->addUser($data);
    }

    /**
     * Tried to changes user data for both containers.
     *
     *  Note type is optional for DB, thus it's needed for MDB and MDB2,
     *  we recommend that you use type even though you use DB, so if you change to MDB[2],
     *  it will be no problem for you.
     *  usage example for updateUser:
     * <code>
     *       $optional = array('is_active' => false);
     *       $admin->updateUser($user_id, 'johndoe', 'dummypass');
     * </code>
     *
     *
     * @param integer $permId permission user id
     * @param  string $handle user handle (username)
     * @param  string $password user password
     * @param  array  $optionalFields values for the optional fields
     * @param  array  $customFields values for the custom fields
     * @param  integer $type permission user type
     * @return mixed   error object or true
     *
     * @access public
     */
    function updateUser($permId, $handle, $password, $optionalFields = array(),
        $customFields = array(), $type = null)
    {
        if (!is_object($this->auth) || !is_object($this->perm)) {
            $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Perm and/or Auth container not set.'));
            return false;
        }

        $permData = $this->perm->getUsers(
            array(
                'fields' => array('auth_user_id', 'auth_container_name'),
                'filters' => array('perm_user_id' => $permId),
                'select' => 'row',
            )
         );

        if (!$permData) {
            $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Could not find user in the permission backend'));
            return false;
        }

        $this->setAdminAuthContainer($permData['auth_container_name']);
        $auth = $this->auth->updateUser($permData['auth_user_id'], $handle,
            $password, $optionalFields, $customFields);

        if ($auth === false) {
            return false;
        }

        if (is_null($type)) {
            return true;
        }

        $data = array(
            'perm_type' => $type
        );
        $filters = array('perm_user_id' => $permId);
        return $this->perm->updateUser($data, $filters);
    }

    /**
    * Removes user from both Perm and Auth containers
    *
    * @param  mixed $permId Perm ID
    * @return  mixed error object or true
    *
    * @access public
    */
    function removeUser($permId)
    {
        if (!is_object($this->auth) || !is_object($this->perm)) {
            $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Perm and/or Auth container not set.'));
            return false;
        }

        $permData = $this->perm->getUsers(
            array(
                'fields' => array('auth_user_id', 'auth_container_name'),
                'filters' => array('perm_user_id' => $permId),
                'select' => 'row',
            )
         );

        if (!$permData) {
            $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Could not find user in the permission backend'));
            return false;
        }

        $this->setAdminAuthContainer($permData['auth_container_name']);
        $result = $this->auth->removeUser($permData['auth_user_id']);

        if ($result === false) {
            return false;
        }

        $filters = array('perm_user_id' => $permId);
        return $this->perm->removeUser($filters);
    }

    /**
    * Finds and gets full userinfo by filtering inside the given container
    *
    * @access public
    * @param  mixed perm filters (as for getUsers() from the perm container
    * @param  boolean if only one row should be returned
    * @return mixed Array with userinfo if found else error object
    */
    function getUsers($container = 'perm', $filter = array(), $first = false)
    {
        if ($container == 'perm') {
            return $this->_getUsersByPerm($filter, $first);
        }
        return $this->_getUsersByAuth($filter, $first);
    }

    /**
    * Finds and gets full userinfo by filtering inside the perm container
    *
    * @param  mixed $permFilter perm filters (as for getUsers() from the perm container
    * @param  boolean $first if only one row should be returned
    * @return mixed Array with userinfo if found else error object
    *
    * @access public
    */
    function _getUsersByPerm($permFilter = array(), $first = false)
    {
        if (!is_object($this->perm)) {
            $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Perm container not set.'));
            return false;
        }

        $permFilter = array('filters' => $permFilter);
        $permFilter['select'] = $first ? 'row' : 'all';
        $permUsers = $this->perm->getUsers($permFilter);
        if (!$permUsers) {
            $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Could not find user in the permission backend'));
            return false;
        }

        if ($first) {
            $permUsers = array($permUsers);
        }

        $users = array();
        foreach($permUsers as $permData) {
            if (!$this->setAdminAuthContainer($permData['auth_container_name'])) {
                $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception',
                    array('msg' => 'Auth container could not be set.'));
                return false;
            }
            $authFilter = array(
                array(
                    'name' => $this->auth->authTableCols['required']['auth_user_id']['name'],
                    'op' => '=',
                    'value' => $permData['auth_user_id'],
                    'cond' => 'AND',
                    'type' => $this->auth->authTableCols['required']['auth_user_id']['type'],
                )
            );

            $authData = $this->auth->getUsers($authFilter);
            if (!$authData) {
                continue;
            }
            $authData = array_shift($authData);

            if ($first) {
                return LiveUser::arrayMergeClobber($permData, $authData);
            }
            $users[] = LiveUser::arrayMergeClobber($permData, $authData);
        }

        return $users;
    }

    /**
    * Finds and gets full userinfo by filtering inside the auth container
    *
    *
    * @param  mixed auth filters (as for getUsers() from the auth container
    * @param  boolean if only one row should be returned
    * @return mixed Array with userinfo if found else error object
    *
    * @access public
    */
    function _getUsersByAuth($authFilter = array(), $first = false)
    {
        if (!is_object($this->auth) || !is_object($this->perm)) {
            $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Perm and/or Auth container not set.'));
            return false;
        }

        $authUsers = $this->auth->getUsers($authFilter);
        if (!$authUsers) {
            $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Could not find user in the authentication backend'));
            return false;
        }

        $users = array();
        foreach($authUsers as $authData) {
            $permData = $this->perm->getUsers(array(
                'filters' => array(
                    'auth_user_id' => $authData['auth_user_id'],
                    'auth_container_name' => $this->authContainerName,
                ),
                'select' => 'row',
            ));
            if (!$permData) {
                continue;
            }

            if ($first) {
                return LiveUser::arrayMergeClobber($authData, $permData);
            }
            $users[] = LiveUser::arrayMergeClobber($authData, $permData);
        }

        return $users;
    }

    /**
     * Wrapper method to get the Error Stack
     *
     * @return array  an array of the errors
     *
     * @access public
     */
    function getErrors()
    {
        if (is_object($this->_stack)) {
            return $this->_stack->getErrors();
        }
        return false;
    }
}
