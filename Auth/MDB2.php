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
 * Require parent class definition and PEAR::MDB2 class.
 */
require_once 'LiveUser/Admin/Auth/Common.php';
require_once 'MDB2.php';

/**
 * Simple MDB2-based complexity driver for LiveUser.
 *
 * Description:
 * This admin class provides the following functionalities
 * - adding users
 * - removing users
 * - update user data (auth related: username, pwd, active)
 * - adding rights
 * - removing rights
 * - get all users
 *
 * @category authentication
 * @package  LiveUser_Admin
 * @author  Lukas Smith <smith@backendmedia.com>
 * @copyright 2002-2005 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version Release: @package_version@
 * @link http://pear.php.net/LiveUser_Admin
 */
class LiveUser_Admin_Auth_MDB2 extends LiveUser_Admin_Auth_Common
{
    /**
     * The DSN that was used to connect to the database (set only if no
     * existing connection object has been reused).
     *
     * @access private
     * @var    string
     */
    var $dsn = null;

    /**
     * PEAR::MDB2 connection object.
     *
     * @access private
     * @var    object
     */
    var $dbc = null;

    /**
     * Auth table
     * Table where the auth data is stored.
     *
     * @access public
     * @var    string
     */
    var $authTable = 'liveuser_users';

    /**
     * Columns of the auth table.
     * Associative array with the names of the auth table columns.
     * The 'auth_user_id', 'handle' and 'passwd' fields have to be set.
     * 'lastlogin', 'is_active', 'owner_user_id' and 'owner_group_id' are optional.
     * It doesn't make sense to set only one of the time columns without the
     * other.
     *
     * @access public
     * @var    array
     */
    var $authTableCols = array(
        'required' => array(
            'auth_user_id' => array('name' => 'auth_user_id', 'type' => ''),
            'handle'       => array('name' => 'handle',       'type' => ''),
            'passwd'       => array('name' => 'passwd',       'type' => ''),
        ),
        'optional' => array(
            'lastlogin'    => array('name' => 'lastlogin',    'type' => ''),
            'is_active'    => array('name' => 'is_active',    'type' => '')
        )
    );

    function init(&$connectOptions)
    {
        if (is_array($connectOptions)) {
            foreach ($connectOptions as $key => $value) {
                if (isset($this->$key)) {
                    $this->$key = $value;
                }
            }
            if (isset($connectOptions['connection']) &&
                MDB2::isConnection($connectOptions['connection'])
            ) {
                $this->dbc     = &$connectOptions['connection'];
            } elseif (isset($connectOptions['dsn'])) {
                $this->dsn = $connectOptions['dsn'];
                $function = null;
                if (isset($connectOptions['function'])) {
                    $function = $connectOptions['function'];
                }
                $options = null;
                if (isset($connectOptions['options'])) {
                    $options = $connectOptions['options'];
                }
                $options['portability'] = MDB2_PORTABILITY_ALL;
                if ($function == 'singleton') {
                    $this->dbc =& MDB2::singleton($connectOptions['dsn'], $options);
                } else {
                    $this->dbc =& MDB2::connect($connectOptions['dsn'], $options);
                }
                if (PEAR::isError($this->dbc)) {
                    $this->_stack->push(LIVEUSER_ERROR_INIT_ERROR, 'error',
                        array('container' => 'could not connect: '.$this->dbc->getMessage()));
                    return false;
                }
            }
        }
        return true;
    } // end func LiveUser_Admin_Auth_MDB2

    /**
     * Adds a new user to Auth/MDB2.
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
        // Generate new user ID
        if (is_null($authId)) {
            $authId = $this->dbc->nextId($this->authTable, true);
            if (PEAR::isError($authId)) {
                $this->_stack->push(
                    LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                    array('reason' => $authId->getMessage() . '-' . $authId->getUserInfo())
                );
                return false;
            }
        }

        $col = $val = array();

        if (isset($this->authTableCols['optional']) && is_array($optionalFields) &&
            count($optionalFields) > 0) {
            foreach ($optionalFields as $alias => $value) {
                $col[] = $this->authTableCols['optional'][$alias]['name'];
                $val[] = $this->dbc->quote($value, $this->authTableCols['optional'][$alias]['type']);
            }
        }

        if (isset($this->authTableCols['custom']) && is_array($customFields) &&
            count($customFields) > 0) {
            foreach ($customFields as $alias => $value) {
                $col[] = $this->authTableCols['custom'][$alias]['name'];
                $val[] = $this->dbc->quote($value, $this->authTableCols['custom'][$alias]['type']);
            }
        }

        if (is_array($col) && count($col) > 0) {
            $col = ',' . implode(',', $col);
            $val = ',' . implode(',', $val);
        } else {
            $col = $val = '';
        }

        // Register new user in auth table
        $query = '
            INSERT INTO
                ' . $this->authTable . '

                (
                ' . $this->authTableCols['required']['auth_user_id']['name'] . ',
                ' . $this->authTableCols['required']['handle']['name'] . ',
                ' . $this->authTableCols['required']['passwd']['name'] . '
                ' . $col . '
                )

            VALUES
                (
                ' . $this->dbc->quote($authId, $this->authTableCols['required']['auth_user_id']['type']) . ',
                ' . $this->dbc->quote($handle, $this->authTableCols['required']['handle']['type']) . ',
                ' . $this->dbc->quote($this->encryptPW($password), $this->authTableCols['required']['passwd']['type']) . '
                ' . $val . '
                )';

        $result = $this->dbc->query($query);

        if (PEAR::isError($result)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => $result->getMessage() . '-' . $result->getUserInfo())
            );
            return false;
        }

        return $authId;
    } // end func addUser

    /**
     * Removes an existing user from Auth/MDB2.
     *
     * @access  public
     * @param   string   Auth user ID of the user that should be removed.
     * @return  mixed    True on success, MDB2 error if not.
     */
    function removeUser($authId)
    {
        // Delete user from auth table (MDB2/Auth)
        $query = '
            DELETE FROM
                ' . $this->authTable . '
            WHERE
                '.$this->authTableCols['required']['auth_user_id']['name'].'=' .
                    $this->dbc->quote($authId, $this->authTableCols['required']['auth_user_id']['type']);

        $result = $this->dbc->query($query);

        if (PEAR::isError($result)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => $result->getMessage() . '-' . $result->getUserInfo())
            );
            return false;
        }

        return true;
    } // end func removeUser

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
        $updateValues = array();
        // Create query.
        $query = '
            UPDATE
                ' . $this->authTable . '
            SET ';

        if (!empty($handle)) {
            $updateValues[] =
                $this->authTableCols['required']['handle']['name'] . ' = '
                    . $this->dbc->quote($handle, $this->authTableCols['required']['handle']['type']);
        }

        if (!empty($password)) {
            $updateValues[] =
                $this->authTableCols['required']['passwd']['name'] . ' = '
                    . $this->dbc->quote($this->encryptPW($password), $this->authTableCols['required']['passwd']['type']);
        }

        if (isset($this->authTableCols['optional']) && is_array($optionalFields) &&
            count($optionalFields) > 0) {
            foreach ($optionalFields as $alias => $value) {
                $updateValues[] = $this->authTableCols['optional'][$alias]['name'] . '=' .
                    $this->dbc->quote($value, $this->authTableCols['optional'][$alias]['type']);
            }
        }

        if (isset($this->authTableCols['custom']) && is_array($customFields) &&
            count($customFields) > 0) {
            foreach ($customFields as $alias => $value) {
                $updateValues[] = $this->authTableCols['custom'][$alias]['name'] . '=' .
                    $this->dbc->quote($value, $this->authTableCols['custom'][$alias]['type']);
            }
        }

        if (count($updateValues)) {
            $query .= implode(', ', $updateValues);
        } else {
            return false;
        }

        $query .= ' WHERE
            ' . $this->authTableCols['required']['auth_user_id']['name'] . '='
                . $this->dbc->quote($authId, $this->authTableCols['required']['auth_user_id']['type']);

        $result = $this->dbc->query($query);

        if (PEAR::isError($result)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => $result->getMessage() . '-' . $result->getUserInfo())
            );
            return false;
        }

        return true;
    }

    /**
     * Gets all users with handle, passwd, auth_user_id
     * lastlogin, is_active and individual rights.
     *
     * The array will look like this:
     * <code>
     * $userData[0]['auth_user_id'] = 'wujha433gawefawfwfiuj2ou9823r98h';
     *             ['handle']       = 'myLogin';
     *             ['passwd']       = 'd346gs2gwaeiuhaeiuuweijfjuwaefhj';
     *             ['lastlogin']    = 1254801292; (Unix timestamp)
     *             ['is_active']    = 1; (1 = yes, 0 = no)
     *             ['owner_user_id']    = 1;
     *             ['owner_group_id']   = 1;
     * </code>
     *
     * The form of filters is to pass an array such as
     *
     * array(
     *     'fieldname' => array('op' => '>', 'value' => 'dummy', 'cond' => ''),
     *     'fieldname' => array('op' => '<', 'value' => 'dummy2', 'cond' => 'OR'),
     * );
     *
     * It can then build relatively complex queries. If you need joins or more
     * complicated queries than that please consider using an alternative
     * solution such as PEAR::DB_DataObject
     *
     * Any aditional field will be returned. The array key will be of the same
     * case it is given.
     *
     * All custom fields defined in the config will be returned with getUsers also.
     *
     * @access  public
     * @param   array  filters to apply to fetched data
     * @param   string  if not null 'ORDER BY $order' will be appended to the query
     * @param   boolean will return an associative array with the auth_user_id
     *                  as the key by using MDB2::getAll() rekeyed.
     * @return  mixed  Array with user data or MDB2 error.
     */
    function getUsers($filters = array(), $order = null, $rekey = false)
    {
        $fields = $where = '';
        $customFields = array();

        $types = array(
            $this->authTableCols['required']['auth_user_id']['type'],
            $this->authTableCols['required']['handle']['type'],
            $this->authTableCols['required']['passwd']['type'],
        );

        if (isset($this->authTableCols['optional']) && count($this->authTableCols['optional']) > 0) {
            foreach ($this->authTableCols['optional'] as $alias => $field_data) {
                $customFields[] = $field_data['name'] . ' AS ' . $alias;
                $types[]        = $field_data['type'];
            }
        }

        if (isset($this->authTableCols['custom']) && count($this->authTableCols['custom']) > 0) {
            foreach ($this->authTableCols['custom'] as $alias => $field_data) {
                $customFields[] = $field_data['name'] . ' AS ' . $alias;
                $types[]        = $field_data['type'];
            }
        }

        if (count($customFields > 0)) {
              $fields  = ',';
              $fields .= implode(',', $customFields);
        }

        if (count($filters) > 0 && is_array($filters)) {
            $where = ' WHERE';
            foreach ($filters as $f => $v) {
                $cond = ' ' . $v['cond'];
                $where .= ' ' . $v['name'] . $v['op'] . $this->dbc->quote($v['value'], $v['type']) . $cond;
            }
            $where = substr($where, 0, -(strlen($cond)));
        }

        if (!is_null($order)) {
            $order = ' ORDER BY ' . $order;
        }

        // First: Get all data from auth table.
        $query = '
            SELECT
                ' . $this->authTableCols['required']['auth_user_id']['name'] . ' AS auth_user_id,
                ' . $this->authTableCols['required']['handle']['name']  . ' AS handle,
                ' . $this->authTableCols['required']['passwd']['name']  . ' AS passwd
                ' . $fields . '
            FROM
                ' . $this->authTable
            . $where
            . $order;

        $result = $this->dbc->queryAll($query, $types, MDB2_FETCHMODE_ASSOC, $rekey);

        if (PEAR::isError($result)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => $result->getMessage() . '-' . $result->getUserInfo())
            );
            return false;
        }

        return $result;
    }
}
?>
