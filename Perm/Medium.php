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
 * @package LiveUser_Admin
 * @author  Markus Wolff <wolff@21st.de>
 * @author  Helgi Þormar Þorbjörnsson <dufuz@php.net>
 * @author  Lukas Smith <smith@pooteeweet.org>
 * @author  Arnaud Limbourg <arnaud@php.net>
 * @author  Christian Dickmann <dickmann@php.net>
 * @author  Matt Scifo <mscifo@php.net>
 * @author  Bjoern Kraus <krausbn@php.net>
 * @copyright 2002-2005 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version $Id$
 * @link http://pear.php.net/LiveUser_Admin
 */

define('LIVEUSER_GROUP_TYPE_ALL',   1);
define('LIVEUSER_GROUP_TYPE_ROLE',  2);
define('LIVEUSER_GROUP_TYPE_USER',  3);

 /**
 * Require parent class definition.
 */
require_once 'LiveUser/Admin/Perm/Simple.php';

/**
 * Medium permission administration class that extends the Simple class with the
 * ability to create, update, remove and assign groups.
 *
 * This class provides a set of functions for implementing a user
 * permission management system on live websites. All authorisation
 * backends/containers must be extensions of this base class.
 *
 * @category authentication
 * @package LiveUser_Admin
 * @author  Markus Wolff <wolff@21st.de>
 * @author  Bjoern Kraus <krausbn@php.net>
 * @author  Helgi Þormar Þorbjörnsson <dufuz@php.net>
 * @copyright 2002-2005 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version Release: @package_version@
 * @link http://pear.php.net/LiveUser_Admin
 */
class LiveUser_Admin_Perm_Medium extends LiveUser_Admin_Perm_Simple
{
    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     */
    function LiveUser_Admin_Perm_Medium()
    {
        $this->LiveUser_Admin_Perm_Simple();
        $this->selectable_tables['getUsers'][] = 'groupusers';
        $this->selectable_tables['getGroups'] = array('groups', 'groupusers', 'grouprights', 'rights', 'translations');
        $this->withFieldMethodMap['group_id'] = 'getGroups';
    }

    /**
     * Add a group
     *
     * @param array containing atleast the key-value-pairs of all required
     *              columns in the group table
     * @return int|bool false on error, true (or new id) on success
     *
     * @access public
     */
    function addGroup($data)
    {
        $result = $this->_storage->insert('groups', $data);
        // notify observer
        return $result;
    }

    /**
     * Update groups
     *
     * @param array containing the key value pairs of columns to update
     * @param array key values pairs (value may be a string or an array)
     *                      This will construct the WHERE clause of your update
     *                      Be careful, if you leave this blank no WHERE clause
     *                      will be used and all groups will be affected by the update
     * @return int|bool false on error, the affected rows on success
     *
     * @access public
     */
    function updateGroup($data, $filters)
    {
        $result = $this->_storage->update('groups', $data, $filters);
        // notify observer
        return $result;
    }

    /**
     * Remove groups and all their relevant relations
     *
     * @param array key values pairs (value may be a string or an array)
     *                      This will construct the WHERE clause of your update
     *                      Be careful, if you leave this blank no WHERE clause
     *                      will be used and all groups will be affected by the removed
     * @return int|bool false on error, the affected rows on success
     *
     * @access public
     */
    function removeGroup($filters)
    {
        $filters = $this->_makeRemoveFilter($filters, 'group_id', 'getGroups');
        if (!$filters) {
            return $filters;
        }

        $result = $this->removeUserFromGroup($filters);
        if ($result === false) {
            return false;
        }

        $result = $this->revokeGroupRight($filters);
        if ($result === false) {
            return false;
        }

        $result = $this->_storage->delete('groups', $filters);
        // notify observer
        return $result;
    }

    /**
     * Grant group a right
     *
     * <code>
     * // grant user idd 13 the right NEWS_CHANGE
     * $data = array(
     *      'right_id'     => NEWS_CHANGE,
     *      'group_id' => 13
     * );
     * $lua->perm->grantGroupRight($data);
     * </code>
     *
     * @param array containing the group_id and right_id and optionally a right_level
     * @return
     *
     * @access public
     */
    function grantGroupRight($data)
    {
        if (!array_key_exists('right_level', $data)) {
            $data['right_level'] = LIVEUSER_MAX_LEVEL;
        }

        // check if the group has already been granted that right
        $filters = array(
            'group_id' => $data['group_id'],
            'right_id' => $data['right_id'],
        );
        $count = $this->_storage->selectCount('grouprights', 'right_id', $filters);
        if ($count > 0) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'This group with id '.$data['group_id'].
                    ' has already been granted the right id '.$data['right_id'])
            );
            return false;
        }

        $result = $this->_storage->insert('grouprights', $data);
        // notify observer
        return $result;
    }

    /**
     * Update right(s) for the given group(s)
     *
     * @param array containing the key value pairs of columns to update
     * @param array key values pairs (value may be a string or an array)
     *                      This will construct the WHERE clause of your update
     *                      Be careful, if you leave this blank no WHERE clause
     *                      will be used and all groups will be affected by the update
     * @return int|bool false on error, the affected rows on success
     *
     * @access public
     */
    function updateGroupRight($data, $filters)
    {
        $result = $this->_storage->update('grouprights', $data, $filters);
        // notify observer
        return $result;
    }

    /**
     * Revoke (remove) right(s) from the group(s)
     *
     * @param array key values pairs (value may be a string or an array)
     *                      This will construct the WHERE clause of your update
     *                      Be careful, if you leave this blank no WHERE clause
     *                      will be used and all groups will be affected by the remove
     * @return int|bool false on error, the affected rows on success
     *
     * @access public
     */
    function revokeGroupRight($filters)
    {
        $result = $this->_storage->delete('grouprights', $filters);
        // notify observer
        return $result;
    }

    /**
     * Add a user to agroup
     *
     * @param array containing the perm_user_id and group_id
     * @return
     *
     * @access public
     */
    function addUserToGroup($data)
    {
        // check if the userhas already been granted added to that group
        $filters = array(
            'perm_user_id' => $data['perm_user_id'],
            'group_id'     => $data['group_id'],
        );
        $count = $this->_storage->selectCount('groupusers', 'group_id', $filters);
        if ($count > 0) {
            return true;
        }

        $result = $this->_storage->insert('groupusers', $data);
        // notify observer
        return $result;
    }

    /**
     * Remove user(s) from group(s)
     *
     * @param array key values pairs (value may be a string or an array)
     *                      This will construct the WHERE clause of your update
     *                      Be careful, if you leave this blank no WHERE clause
     *                      will be used and all users will be affected by the remove
     * @return int|bool false on error, the affected rows on success
     *
     * @access public
     */
    function removeUserFromGroup($filters)
    {
        $result = $this->_storage->delete('groupusers', $filters);
        // notify observer
        return $result;
    }

    /**
     * Fetches rights
     *
     * @param array containing key-value pairs for:
     *                 'fields'  - ordered array containing the fields to fetch
     *                             if empty all fields from the user table are fetched
     *                 'filters' - key values pairs (value may be a string or an array)
     *                 'orders'  - key value pairs (values 'ASC' or 'DESC')
     *                 'rekey'   - if set to true, returned array will have the
     *                             first column as its first dimension
     *                 'group'   - if set to true and $rekey is set to true, then
     *                             all values with the same first column will be
     *                             wrapped in an array
     *                 'limit'   - number of rows to select
     *                 'offset'  - first row to select
     *                 'select'  - determines what query method to use:
     *                             'one' -> queryOne, 'row' -> queryRow,
     *                             'col' -> queryCol, 'all' ->queryAll (default)
     * @param bool determines if joins should be done using the 'userrights'
     *              (default) or through the 'grouprights' and 'groupusers' tables
     * @return bool|array false on failure or array with selected data
     *
     * @access public
     */
    function getRights($params = array())
    {
        $selectable_tables = $this->selectable_tables['getRights'];
        $root_table = reset($selectable_tables);
        if (array_key_exists('by_group', $params)
            && $params['by_group']
            && !in_array('grouprights', $selectable_tables)
        ) {
            unset($params['by_group']);
            $key = array_search('userrights', $selectable_tables);
            if ($key) {
                $selectable_tables[0] = 'groupusers';
                $selectable_tables[$key] = 'grouprights';
                array_unshift($selectable_tables, $root_table);
            } else {
                $selectable_tables[0] = 'groupusers';
                array_unshift($selectable_tables, 'grouprights');
                array_unshift($selectable_tables, $root_table);
            }
        }

        return $this->_makeGet($params, $root_table, $selectable_tables);
    }

    /**
     * Remove rights and all their relevant relations
     *
     * @param array key values pairs (value may be a string or an array)
     *                      This will construct the WHERE clause of your update
     *                      Be careful, if you leave this blank no WHERE clause
     *                      will be used and all rights will be affected by the remove
     * @return int|bool false on error, the affected rows on success
     *
     * @access public
     */
    function removeRight($filters)
    {
        $filters = $this->_makeRemoveFilter($filters, 'right_id', 'getRights');
        if (!$filters) {
            return $filters;
        }

        $result = $this->revokeGroupRight($filters);
        if ($result === false) {
            return false;
        }

        return parent::removeRight($filters);
    }

    /**
     * Remove users and all their relevant relations
     *
     * @param array key values pairs (value may be a string or an array)
     *                      This will construct the WHERE clause of your update
     *                      Be careful, if you leave this blank no WHERE clause
     *                      will be used and all users will be affected by the removed
     * @return int|bool false on error, the affected rows on success
     *
     * @access public
     */
    function removeUser($filters)
    {
        $filters = $this->_makeRemoveFilter($filters, 'perm_user_id', 'getUsers');
        if (!$filters) {
            return $filters;
        }

        $result = $this->removeUserFromGroup($filters);
        if ($result === false) {
            return false;
        }

        return parent::removeUser($filters);
    }

    /**
     * Fetches groups
     *
     * @param array containing key-value pairs for:
     *                 'fields'  - ordered array containing the fields to fetch
     *                             if empty all fields from the user table are fetched
     *                 'filters' - key values pairs (value may be a string or an array)
     *                 'orders'  - key value pairs (values 'ASC' or 'DESC')
     *                 'rekey'   - if set to true, returned array will have the
     *                             first column as its first dimension
     *                 'group'   - if set to true and $rekey is set to true, then
     *                             all values with the same first column will be
     *                             wrapped in an array
     *                 'limit'   - number of rows to select
     *                 'offset'  - first row to select
     *                 'select'  - determines what query method to use:
     *                             'one' -> queryOne, 'row' -> queryRow,
     *                             'col' -> queryCol, 'all' ->queryAll (default)
     * @return bool|array false on failure or array with selected data
     *
     * @access public
     */
    function getGroups($params = array())
    {
        $selectable_tables = $this->selectable_tables['getGroups'];
        $root_table = reset($selectable_tables);

        return $this->_makeGet($params, $root_table, $selectable_tables);
    }
}
?>
