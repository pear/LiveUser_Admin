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

/**
 * Base class for authentication backends.
 *
 * @category authentication
 * @package  LiveUser_Admin
 * @author   Lukas Smith <smith@backendmedia.com>
 * @copyright 2002-2005 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version Release: @package_version@
 * @link http://pear.php.net/LiveUser_Admin
 */
class LiveUser_Admin_Auth_Common
{
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
    var $name = null;

    /**
     * Class constructor. Feel free to override in backend subclasses.
     *
     * @access protected
     */
    function LiveUser_Admin_Auth_Common(&$connectOptions, $name = null)
    {
        $this->_stack = &PEAR_ErrorStack::singleton('LiveUser_Admin');

        if (is_array($connectOptions)) {
            foreach ($connectOptions as $key => $value) {
                if (isset($this->$key)) {
                    $this->$key = $value;
                }
            }
        }
        if (!is_null($name)) {
            $this->name = $name;
        }
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
     * Function returns the inquired value if it exists in the class.
     *
     * @access public
     * @param  string   Name of the property to be returned.
     * @return mixed    null, a value or an array.
     */
    function getProperty($what)
    {
        $that = null;
        if (isset($this->$what)) {
            $that = $this->$what;
        }
        return $that;
    }

    /**
     * Adds a new user to Auth.
     *
     * @access  public
     * @param   string  Handle (username).
     * @param   string  Password.
     * @param   array   Array of optional fields values to be added array('alias' => ''value')
     * @param   array   Array of custom fields values to be added array('alias' => ''value')
     * @param   mixed   If specificed no new ID will be automatically generated instead
     * @return  mixed   Users auth ID on success, DB error if not, false if not initialized
     */
    function addUser($handle, $password = '', $optionalFields = array(),
                              $customFields = array(), $authId = null)
    {
        return LiveUser_Admin::raiseError(LIVEUSER_ADMIN_ERROR_NOT_SUPPORTED, null, null,
            'addUser(): Method not supported by this container');
    }

    /**
     * Removes an existing user from Auth.
     *
     * @access  public
     * @param   string   Auth user ID of the user that should be removed.
     * @return  mixed    True on success, error object if not.
     */
    function removeUser($authId)
    {
        return LiveUser_Admin::raiseError(LIVEUSER_ADMIN_ERROR_NOT_SUPPORTED, null, null,
            'removeUser(): Method not supported by this container');
    }

    /**
     * Changes user data in auth table.
     *
     * @access  public
     * @param   string   Auth user ID.
     * @param   string   Handle (username) (optional).
     * @param   string   Password (optional).
     * @param   array   Array of optional fields values to be added array('alias' => ''value')
     * @param   array    Array of custom fields values to be updated
     * @return  mixed    True on success, DB error if not.
     */
    function updateUser($authId, $handle = '', $password = '',
                                   $optionalFields = array(), $customFields = array())
    {
        return LiveUser_Admin::raiseError(LIVEUSER_ADMIN_ERROR_NOT_SUPPORTED, null, null,
            'updateUser(): Method not supported by this container');
    }

    /**
     * Gets all users with handle, passwd, authId,
     * lastlogin, is_active and individual rights.
     *
     * The array will look like this:
     * <code>
     * $userData[0]['auth_user_id']       = 'wujha433gawefawfwfiuj2ou9823r98h';
     *             ['handle']       = 'myLogin';
     *             ['passwd']     = 'd346gs2gwaeiuhaeiuuweijfjuwaefhj';
     *             ['lastlogin']    = 1254801292; (Unix timestamp)
     *             ['is_active']     = 1; (1 = yes, 0 = no)
     * </code>
     *
     * @access  public
     * @param   array  filters to apply to fetched data
     * @param   string  if not null 'ORDER BY $order' will be appended to the query
     * @param   boolean will return an associative array with the auth_user_id
     *                  as the key by using DB::getAssoc() instead of DB::getAll()
     * @return  mixed  Array with user data or DB error.
     */
    function getUsers($filters = array(), $order = null, $rekey = false)
    {
        return LiveUser_Admin::raiseError(LIVEUSER_ADMIN_ERROR_NOT_SUPPORTED, null, null,
            'getUsers(): Method not supported by this container');
    }
}
