<?php
// LiveUser: A framework for authentication and authorization in PHP applications
// Copyright (C) 2002-2003 Markus Wolff
//
// This library is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public
// License as published by the Free Software Foundation; either
// version 2.1 of the License, or (at your option) any later version.
//
// This library is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
// Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public
// License along with this library; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

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
 * $found = $admin->getUser(3);
 *
 * if ($found) {
 *  var_dump($admin->perm->getRights());
 * }
 * </code>
 *
 * @see     LiveUser::factory()
 * @author  Lukas Smith
 * @author  Arnaud Limbourg
 * @author Helgi Þormar Þorbjörnsson
 * @version $Id$
 * @package LiveUser
 */
class LiveUser_Admin
{

     /**
      * Name of the current selected auth container
      *
      * @access public
      * @var    string
      */
     var $authContainerName;

    /**
     * Array containing the auth objects.
     *
     * @access private
     * @var    array
     */
    var $_authContainers = array();

    /**
     * Admin perm object
     *
     * @access public
     * @var    object
     */
    var $perm = null;

    /**
     * Auth admin object
     *
     * @access public
     * @var    object
     */
    var $auth = null;

    /**
     * Configuration array
     *
     * @access private
     * @var    array
     */
     var $_conf = array();

    /**
     * Error codes to message mapping array
     *
     * @access private
     * @var    array
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
     * @access private
     * @var    Log
     */
    var $_log = null;

    /**
     * For lazy loading of PEAR::Log
     *
     * @acess private
     * @var   boolean
     */
    var $_log_loaded = false;

    function LiveUser_Admin()
    {
        $this->_stack = &PEAR_ErrorStack::singleton('LiveUser_Admin');

        if ($GLOBALS['_LIVEUSER_DEBUG']) {
            if (!$this->_log_loaded) {
                $this->loadPEARLog();
            }
            $this->_log->addChild(Log::factory('win', 'LiveUser_Admin'));
        }

        $this->_stack->setErrorMessageTemplate($this->_errorMessages);
    }

    /**
     * This method lazy loads PEAR::Log
     *
     * @access protected
     * @return void
     */
    function loadPEARLog()
    {
        require 'Log.php';
        $this->_log = &Log::factory('composite');
        $this->_stack->setLogger($this->_log);
        $this->_log_loaded = true;
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
     * @access public
     * @param  Log     logger instance
     * @return boolean true on success or false on failure
     */
    function addErrorLog(&$log)
    {
        if (!$this->_log_loaded) {
            $this->loadPEARLog();
        }
        return $this->_log->addChild($log);
    }

    /**
     *
     * @access public
     * @return object
     */
    function &factory($conf)
    {
        $obj = &new LiveUser_Admin;

        if (is_array($conf)) {
            $obj->_conf = $conf;
        }

        if (isset($obj->_options['autoInit']) && $obj->_options['autoInit']) {
            $this->setAdminContainers();
        }

        return $obj;
    }

    /**
     *
     * @access public
     * @return object
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
     * @param  array   configuration array
     * @return boolean true upon success, false otherwise
     */
    function setConfArray($conf)
    {
        if (!is_array($conf)) {
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
     * @access public
     * @param  string   auth container name
     * @return boolean true upon success, false otherwise
     */
    function setAdminAuthContainer($authName)
    {
        if (!isset($this->_authContainers[$authName])
            || !is_object($this->_authContainers[$authName])
        ) {
            if (!isset($this->_conf['authContainers'][$authName])) {
                return false;
            }
            $this->_authContainers[$authName] = &LiveUser::authFactory(
                $this->_conf['authContainers'][$authName],
                $authName,
                'LiveUser_Admin_'
            );
            if (!$this->_authContainers[$authName]) {
                $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception', array('msg' => 'Could not create auth container instance'));
                return false;
            }
        }
        $this->authContainerName = $authName;
        $this->auth = &$this->_authContainers[$authName];
        return true;
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
     * @access public
     * @return boolean true upon success, false otherwise
     */
    function setAdminPermContainer()
    {
        if (!is_array($this->_conf)) {
            return false;
        }

        $this->perm = &LiveUser::permFactory($this->_conf['permContainer'], 'LiveUser_Admin_');
        return true;
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
     * @access public
     * @param  mixed   user auth id
     * @param  string   auth container name
     * @return boolean true upon success, false otherwise
     */
    function setAdminContainers($authId = null, $authName = null)
    {
        if (!is_array($this->_conf)) {
            return false;
        }

        if (is_null($authName)) {
            if (is_null($authId)) {
                reset($this->_conf['authContainers']);
                $authName = key($this->_conf['authContainers']);
            } else {
                foreach ($this->_conf['authContainers'] as $k => $v) {
                    if (!isset($this->_authContainers[$k]) ||
                        !is_object($this->_authContainers[$k])
                    ) {
                        $this->_authContainers[$k] = &$this->authFactory($v, $k, 'LiveUser_Admin_');
                    }

                    if (!is_null($authId)) {
                        $match = $this->_authContainers[$k]->getUsers(array('auth_user_id' => $authId));
                        if (is_array($match) && sizeof($match) > 0) {
                            $authName = $k;
                            break;
                        }
                    }
                }
            }
        }

        if (isset($authName)) {
            if (!isset($this->perm) || !is_object($this->perm)) {
                $perm_res = $this->setAdminPermContainer();
            }
            $auth_res = $this->setAdminAuthContainer($authName);
            return (!$perm_res || !$auth_res) ? false : true;
        }

        return false;
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
     * Untested: it most likely doesn't work.
     *
     * @access public
     * @param  string  user handle (username)
     * @param  string  user password
     * @param  array   values for the optional fields
     * @param  array   values for the custom fields
     * @param  int          ID
     * @param  integer permission user type
     * @return mixed   userid or false
     */
    function addUser($handle, $password, $optionalFields = array(), $customFields = array(),
                             $id = null, $type = LIVEUSER_USER_TYPE_ID)
    {
        if (is_object($this->auth) && is_object($this->perm)) {
            $authId = $this->auth->addUser($handle, $password, $optionalFields,
                                                            $customFields, $id);

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

        $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception', array('msg' => 'Perm or Auth container couldn\t be started.'));
        return false;
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
     * Untested: it most likely doesn't work.
     *
     * @access public
     * @param integer permission user id
     * @param  string  user handle (username)
     * @param  string  user password
     * @param  array   values for the optional fields
     * @param  array   values for the custom fields
     * @param  integer permission user type
     * @return mixed   error object or true
     */
    function updateUser($permId, $handle, $password, $optionalFields = array(),
                                  $customFields = array(), $type = LIVEUSER_USER_TYPE_ID)
    {
        if (is_object($this->auth) && is_object($this->perm)) {
            $authData = $this->perm->getUsers(array(
                'filters' => array($this->perm->getAlias('perm_user_id') => $permId),
                'fields' => array($this->perm->getAlias('auth_user_id')))
             );

            if (!$authData) {
                return $authData;
            }

            $authData = reset($authData);
            $auth = $this->auth->updateUser($authData[$this->perm->getAlias('auth_user_id')], $handle, $password,
                                                             $optionalFields, $customFields);

            if (PEAR::isError($auth)) {
                return $auth;
            }

            $data = array(
                $this->perm->getAlias('perm_type') => $type
            );
            $filters = array('perm_user_id' => $permId);
            return $this->perm->updateUser($data, $filters);
        }

        $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception', array('msg' => 'Perm or Auth container couldn\t be started.'));
        return false;
    }

    /**
    * Removes user from both containers
    *
    * Untested: it most likely doesn't work.
    *
    * @access public
    * @param  mixed Auth ID
    * @return  mixed error object or true
    */
    function removeUser($permId)
    {
        if (is_object($this->auth) && is_object($this->perm)) {
            $authData = $this->perm->getUsers(array(
                'filters' => array($this->perm->getAlias('perm_user_id') => $permId),
                'fields' => array($this->perm->getAlias('auth_user_id')))
             );

            if (!$authData) {
                return $authData;
            }

            $authData = reset($authData);
            $result = $this->auth->removeUser($authData[$this->perm->getAlias('auth_user_id')]);

            if (PEAR::isError($result)) {
                return $result;
            }

            $filters = array($this->perm->getAlias('perm_user_id') => $permId);
            return $this->perm->removeUser($filters);
        }

        $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception', array('msg' => 'Perm or Auth container couldn\t be started.'));
        return false;
    }

    /**
    * Searches users with given filters and returns
    * all users found with their handle, passwd, auth_user_id
    * lastlogin, is_active and the customFields if they are specified
    *
    * Untested: it most likely doesn't work.
    *
    * @access public
    * @param   array   filters to apply to fetched data
    * @param   string  if not null 'ORDER BY $order' will be appended to the query
    * @param   boolean will return an associative array with the auth_user_id
    *                  as the key by using DB::getAssoc() instead of DB::getAll()
    * @return mixed error object or array
    */
    function searchUsers($filters = array(), $order = null, $rekey = false)
    {
        if (is_object($this->auth) && is_object($this->perm)) {
            $search = $this->auth->getUsers($filters, $order, $rekey);

            if (PEAR::isError($search)) {
                return $search;
            }

            foreach($search as $key => $user) {
                $permFilter[$this->perm->getAlias('auth_user_id')] = $user['auth_user_id'];
                $permData = $this->perm->getUsers(array('filters' => $permFilter));
                if (!$permData) {
                    return false;
                }
                $search[$key] = LiveUser::arrayMergeClobber(reset($permData), $user);
            }
            return $search;
        }

        $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception', array('msg' => 'Perm or Auth container couldn\t be started.'));
        return false;
    }

    /**
    * Finds and gets userinfo by his userID, customFields can
    *  also be gotten
    *
    * Untested: it most likely doesn't work.
    *
    * @access public
    * @param  mixed  Perm User ID
    * @return mixed Array with userinfo if found else error object
    */
    function getUser($permId, $permFilter = array(), $authFilter = array(),
        $permOptions = array())
    {
        if (is_object($this->auth) && is_object($this->perm)) {
            $permFilter[$this->perm->getAlias('perm_user_id')] = $permId;
            $permData = $this->perm->getUsers(array('filters' => $permFilter));
            if (!$permData) {
                return false;
            }

            $permData = array_shift($permData);

            $authFilter = array(
                array(
                    'name' => $this->auth->authTableCols['required']['auth_user_id']['name'],
                    'op' => '=',
                    'value' => $permData[$this->perm->getAlias('auth_user_id')],
                    'cond' => 'AND',
                    'type' => $this->auth->authTableCols['required']['auth_user_id']['type'],
                )
            );

            $authData = $this->auth->getUsers($authFilter);
            if (PEAR::isError($authData)) {
                return $authData;
            }

            $authData = array_shift($authData);

            return LiveUser::arrayMergeClobber($permData, $authData);
        }

        $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception', array('msg' => 'Perm or Auth container couldn\t be started.'));
        return false;
    }

    /**
     * Wrapper method to get the Error Stack
     *
     * @access public
     * @return array  an array of the errors
     */
    function getErrors()
    {
        return $this->_stack->getErrors();
    }
}
