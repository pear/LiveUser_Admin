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

/**
 * Attempt at a unified admin class
 *
 * Simple usage:
 *
 * <code>
 * $admin = new LiveUser_Admin($conf, 'FR');
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
      * Language to be used
      *
      * @access public
      * @var    string
      */
     var $lang = '';

    /**
     * Constructor
     *
     * @access protected
     * @param  array  liveuser conf array
     * @param  string two letters language code
     * @return void
     */
    function LiveUser_Admin($conf, $lang)
    {
        if (is_array($conf)) {
            $this->_conf = $conf;
        }
        $this->lang = $lang;

        if (isset($this->_conf['autoInit']) && $this->_conf['autoInit'] === true) {
            $this->setAdminContainers();
        }
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
    * Makes your instance global.
    *
    * <b>You MUST call this method with the $var = &LiveUser_Admin::singleton() syntax.
    * Without the ampersand (&) in front of the method name, you will not get
    * a reference, you will get a copy.</b>
    *
    * @access public
    * @param  array  liveuser conf array
    * @param  string two letters language code
    * @return object      Returns an object of either LiveUser or PEAR_Error type
    * @see    LiveUser_Admin::LiveUser_Admin
    */
    function &singleton($conf, $lang)
    {
        static $instances;
        if (!isset($instances)) $instances = array();

        $signature = serialize(array($conf, $lang));
        if (!isset($instances[$signature])) {
            $obj = &LiveUser_Admin::LiveUser_Admin($conf, $lang);
            if(PEAR::isError($obj)) {
                return $obj;
            }
            $instances[$signature] =& $obj;
        }

        return $instances[$signature];
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
            $this->_authContainers[$authName] =
                &LiveUser::authFactory($this->_conf['authContainers'][$authName], $authName, true);
            if (LiveUser::isError($this->_authContainers[$authName])) {
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

        $this->perm = &LiveUser::permFactory($this->_conf['permContainer'], true);
        $this->perm->setCurrentLanguage($this->lang);
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
                        $this->_authContainers[$k] = &$this->authFactory($v, $k, true);
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

            if (LiveUser::isError($authId)) {
                return $authId;
            }

            return $this->perm->addUser($authId, $this->authContainerName, $type);
        }
        return LiveUser_Admin::raiseError(LIVEUSER_ERROR, null, null,
                    'Perm or Auth container couldn\t be started.');
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
            $authData = $this->perm->getAuthUserId($permId);

            $auth = $this->auth->updateUser($authData['auth_user_id'], $handle, $password,
                                                             $optionalFields, $customFields);

            if (LiveUser::isError($auth)) {
                return $auth;
            }

            return $this->perm->updateUser($permId, $authData['auth_user_id'], $this->authContainerName, $type);
        }
        return LiveUser_Admin::raiseError(LIVEUSER_ERROR, null, null,
                    'Perm or Auth container couldn\t be started.');
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
            $authData = $this->perm->getAuthUserId($permId);

            if (LiveUser::isError($authData)) {
                return $authData;
            }

            $result = $this->auth->removeUser($authData['auth_user_id']);

            if (LiveUser::isError($result)) {
                return $result;
            }

            return $this->perm->removeUser($permId);
        }
        return LiveUser_Admin::raiseError(LIVEUSER_ERROR, null, null,
                    'Perm or Auth container couldn\t be started.');
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

            if (LiveUser::isError($search)) {
                return $search;
            }

            return $search;
        }
        return LiveUser_Admin::raiseError(LIVEUSER_ERROR, null, null,
                    'Perm or Auth container couldn\t be started.');
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
            $permFilter['perm_user_id'] = $permId;
            $permData = $this->perm->getUsers($permFilter, $permOptions);
            if (LiveUser::isError($permData)) {
                return $permData;
            }

            $permData = array_shift($permData);

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
            if (LiveUser::isError($authData)) {
                return $authData;
            }

            $authData = array_shift($authData);

            return LiveUser::arrayMergeClobber($permData, $authData);
        }
        return LiveUser_Admin::raiseError(LIVEUSER_ERROR, null, null,
                    'Perm or Auth container couldn\t be started.');
    }

    /**
     * This method is used to communicate an error and invoke error
     * callbacks etc.  Basically a wrapper for PEAR::raiseError
     * without the message string.
     *
     * @param mixed    integer error code, or a PEAR error object (all
     *                 other parameters are ignored if this parameter is
     *                 an object
     *
     * @param int      error mode, see PEAR_Error docs
     *
     * @param mixed    If error mode is PEAR_ERROR_TRIGGER, this is the
     *                 error level (E_USER_NOTICE etc).  If error mode is
     *                 PEAR_ERROR_CALLBACK, this is the callback function,
     *                 either as a function name, or as an array of an
     *                 object and method name.  For other error modes this
     *                 parameter is ignored.
     *
     * @param string   Extra debug information.  Defaults to the last
     *                 query and native error code.
     *
     * @return object  a PEAR error object
     *
     * @see PEAR_Error
     */
    function &raiseError($code = null, $mode = null, $options = null,
                         $userinfo = null)
    {
        // The error is yet a LiveUser error object
        if (is_object($code)) {
            return PEAR::raiseError($code, null, null, null, null, null, true);
        }

        if (empty($code)) {
            $code = LIVEUSER_ERROR;
        }
        $msg = LiveUser::errorMessage($code);
        return PEAR::raiseError("LiveUser Error: $msg", $code, $mode, $options, $userinfo);
    }
}