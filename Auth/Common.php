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
 * @author  Lukas Smith <smith@pooteeweet.org>
 * @author Arnaud Limbourg <arnaud@php.net>
 * @author  Christian Dickmann <dickmann@php.net>
 * @author  Matt Scifo <mscifo@php.net>
 * @author  Bjoern Kraus <krausbn@php.net>
 * @copyright 2002-2005 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version CVS: $Id$
 * @link http://pear.php.net/LiveUser_Admin
 */

/**
 * Base class for authentication backends.
 *
 * @category authentication
 * @package  LiveUser_Admin
 * @author   Lukas Smith <smith@pooteeweet.org>
 * @copyright 2002-2005 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version Release: @package_version@
 * @link http://pear.php.net/LiveUser_Admin
 */
class LiveUser_Admin_Auth_Common
{
    /**
     * Error stack
     *
     * @var object PEAR_ErrorStack
     * @access private
     */
    var $_stack = null;

    /**
     * Storage Container
     *
     * @var object
     * @access private
     */
    var $_storage = null;

    /**
     * Set posible encryption modes.
     *
     * @access private
     * @var    array
     */
    var $encryptionModes = array('MD5'   => 'MD5',
                                 'RC4'   => 'RC4',
                                 'PLAIN' => 'PLAIN',
                                 'SHA1'  => 'SHA1');

    /**
     * Defines the algorithm used for encrypting/decrypting
     * passwords. Default: "MD5".
     *
     * @access private
     * @var    string
     */
    var $passwordEncryptionMode = 'MD5';

    /**
     * Defines the secret to use for encryption if needed
     *
     * @access protected
     * @var    string
     */
    var $secret;

    /**
     * The name associated with this auth container. The name is used
     * when adding users from this container to the reference table
     * in the permission container. This way it is possible to see
     * from which auth container the user data is coming from.
     *
     * @var    string
     * @access public
     */
    var $containerName = null;

    /**
     * Allow multiple users in the database to have the same
     * login handle. Default: false.
     *
     * @var    boolean
     */
    var $allowDuplicateHandles = false;

    /**
     * Allow empty passwords to be passed to LiveUser. Default: false.
     *
     * @var    boolean
     */
    var $allowEmptyPasswords = false;

    /**
     * Class constructor. Feel free to override in backend subclasses.
     *
     * @access protected
     */
    function LiveUser_Admin_Auth_Common()
    {
        $this->_stack = &PEAR_ErrorStack::singleton('LiveUser_Admin');
    }

    /**
     * Load the storage container
     *
     * @access  public
     * @param  mixed         Name of array containing the configuration.
     * @return  boolean true on success or false on failure
     */
    function init(&$conf, $containerName)
    {
        $this->containerName = $containerName;
        if (!array_key_exists('storage', $conf)) {
            $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Missing storage configuration array'));
            return false;
        }

        if (is_array($conf)) {
            $keys = array_keys($conf);
            foreach ($keys as $key) {
                if (isset($this->$key)) {
                    $this->$key =& $conf[$key];
                }
            }
        }

        $storageConf = array();
        $storageConf[$conf['type']] =& $conf['storage'];
        $this->_storage = LiveUser::storageFactory($storageConf, 'LiveUser_Admin_Auth_');
        if ($this->_storage === false) {
            $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Could not instanciate storage container'));
            return false;
        }

        return true;
    }

    /**
     * Decrypts a password so that it can be compared with the user
     * input. Uses the algorithm defined in the passwordEncryptionMode
     * property.
     *
     * @param  string the encrypted password
     * @return string The decrypted password
     */
    function decryptPW($encryptedPW)
    {
        $decryptedPW = 'Encryption type not supported.';

        switch (strtoupper($this->passwordEncryptionMode)) {
        case 'PLAIN':
            $decryptedPW = $encryptedPW;
            break;
        case 'MD5':
            // MD5 can't be decoded, so return the string unmodified
            $decryptedPW = $encryptedPW;
            break;
        case 'RC4':
            $decryptedPW = LiveUser::cryptRC4($decryptedPW, $this->secret, false);
            break;
        case 'SHA1':
            // SHA1 can't be decoded, so return the string unmodified
            $decryptedPW = $encryptedPW;
            break;
        }

        return $decryptedPW;
    }

    /**
     * Encrypts a password for storage in a backend container.
     * Uses the algorithm defined in the passwordEncryptionMode
     * property.
     *
     * @param string  encryption type
     * @return string The encrypted password
     */
    function encryptPW($plainPW)
    {
        $encryptedPW = 'Encryption type not supported.';

        switch (strtoupper($this->passwordEncryptionMode)) {
        case 'PLAIN':
            $encryptedPW = $plainPW;
            break;
        case 'MD5':
            $encryptedPW = md5($plainPW);
            break;
        case 'RC4':
            $encryptedPW = LiveUser::cryptRC4($plainPW, $this->secret, true);
            break;
        case 'SHA1':
            if (!function_exists('sha1')) {
                $this->_stack->push(LIVEUSER_ERROR_NOT_SUPPORTED,
                    'exception', array(), 'SHA1 function doesn\'t exist. Upgrade your PHP version');
                return false;
            }
            $encryptedPW = sha1($plainPW);
            break;
        }

        return $encryptedPW;
    }

    /**
     * Add user
     *
     *
     * @param array $data
     * @return
     *
     * @access public
     */
    function addUser($data)
    {
        if (array_key_exists('passwd', $data)) {
            $data['passwd'] = $this->encryptPW($data['passwd']);
        }
        $result = $this->_storage->insert('users', $data);
        // notify observer
        return $result;
    }

    /**
     * Update usr
     *
     *
     * @param array $data
     * @param array $filters
     * @return
     *
     * @access public
     */
    function updateUser($data, $filters)
    {
        if (array_key_exists('passwd', $data)) {
            $data['passwd'] = $this->encryptPW($data['passwd']);
        }
        $result = $this->_storage->update('users', $data, $filters);
        // notify observer
        return $result;
    }

    /**
     * Remove user
     *
     *
     * @param array $filters Array containing the filters on what user(s)
     *                       should be removed
     * @return
     *
     * @access public
     */
    function removeUser($filters)
    {
        $result = $this->_storage->delete('users', $filters);
        // notify observer
        return $result;
    }
    /**
     * Fetches users
     *
     *
     * @param array $params
     * @return
     *
     * @access public
     */
    function getUsers($params = array())
    {
        $fields = array_key_exists('fields', $params) ? $params['fields'] : array();
        $filters = array_key_exists('filters', $params) ? $params['filters'] : array();
        $orders = array_key_exists('orders', $params) ? $params['orders'] : array();
        $rekey = array_key_exists('rekey', $params) ? $params['rekey'] : false;
        $group = array_key_exists('group', $params) ? $params['group'] : false;
        $limit = array_key_exists('limit', $params) ? $params['limit'] : null;
        $offset = array_key_exists('offset', $params) ? $params['offset'] : null;
        $select = array_key_exists('select', $params) ? $params['select'] : 'all';

        return $this->_storage->select($select, $fields, $filters, $orders,
            $rekey, $group, $limit, $offset, 'users', array('users'));
    }

    /**
     * properly disconnect from resources
     *
     * @access  public
     */
    function disconnect()
    {
        $this->_storage->disconnect();
    }
}
