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

/**
 * Medium container for permission handling
 *
 * @package  LiveUser_Admin
 * @category authentication
 */

define('LIVEUSER_GROUP_TYPE_ALL',   1);
define('LIVEUSER_GROUP_TYPE_ROLE',  2);
define('LIVEUSER_GROUP_TYPE_USER',  3);

 /**
 * Require parent class definition.
 */
require_once 'LiveUser/Admin/Perm/Simple.php';

/**
 * This class provides a set of functions for implementing a user
 * permission management system on live websites. All authorisation
 * backends/containers must be extensions of this base class.
 *
 * @author  Markus Wolff <wolff@21st.de>
 * @author  Bjoern Kraus <krausbn@php.net>
 * @version $Id$
 * @package LiveUser
 * @category authentication
 */
class LiveUser_Admin_Perm_Medium extends LiveUser_Admin_Perm_Simple
{

    /**
     * Constructor
     *
     * @access protected
     * @param  mixed      configuration array
     * @return void
     */
    function LiveUser_Perm_Medium(&$confArray)
    {
        $this->LiveUser_Perm_Simple($confArray);
    }

    /**
     *
     *
     * @access public
     * @param array $data
     * @return
     */
    function addGroup($data)
    {
        // sanity checks
        if (isset($data[$this->alias['group_id']]) && !is_numeric($data[$this->alias['group_id']])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => $this->alias['group_id'])
            );
            return false;
        }

        $result = $this->_storage->insert('groups', $data);
        // notify observer
        return $result;
    }

    /**
     *
     *
     * @access public
     * @param array $data
     * @param array $filters
     * @return
     */
    function updateGroup($data, $filters)
    {
        // sanity checks
        if (!isset($filters[$this->alias['group_id']]) || !is_numeric($filters[$this->alias['group_id']])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->alias['group_id'])
            );
            return false;
        }

        $result = $this->_storage->update('groups', $data, $filters);
        // notify observer
        return $result;
    }

    /**
     *
     *
     * @access public
     * @param array $filters
     * @return
     */
    function removeGroup($filters)
    {
        // sanity checks
        if (!isset($filters[$this->alias['group_id']]) || !is_numeric($filters[$this->alias['group_id']])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->alias['group_id'])
            );
            return false;
        }

        // Remove users from the group
        $filter = array($this->alias['group_id'] => $filters[$this->alias['group_id']]);
        $result = $this->_storage->delete('groupusers', $filter);
        if (!$result) {
            return false;
        }

        // Delete group rights
        $result = $this->revokeGroupRight($filters);
        if (!$result) {
            return false;
        }

        $result = $this->_storage->delete('groups', $filters);
        // notify observer
        return $result;
    }

    /**
     *
     *
     * @access public
     * @param array $data
     * @return
     */
    function grantGroupRight($data)
    {
        // sanity checks
        if (!isset($data[$this->alias['group_id']]) || !is_numeric($data[$this->alias['group_id']])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => $this->alias['group_id'])
            );
            return false;
        }

        if (!isset($data[$this->alias['right_id']]) || !is_numeric($data[$this->alias['right_id']])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => $this->alias['right_id'])
            );
            return false;
        }

        if (!isset($data[$this->alias['right_level']])) {
            $data[$this->alias['right_level']] = LIVEUSER_MAX_LEVEL;
        }

        // check if the group has already been granted that right
        $filters = array(
                       $this->alias['group_id'] => $data[$this->alias['group_id']],
                       $this->alias['right_id']     => $data[$this->alias['right_id']],
                   );
        $count = $this->_storage->selectOne('grouprights', $this->alias['right_id'], $filters, true);
        if ($count > 0) {
            return true;
        }

        $result = $this->_storage->insert('grouprights', $data);
        // notify observer
        return $result;
    }

    /**
     *
     *
     * @access public
     * @param array $data
     * @param array $filters
     * @return
     */
    function updateGroupRight($data, $filters)
    {
        // sanity checks
        if (!isset($data[$this->alias['right_level']])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => $this->alias['right_level'])
            );
            return false;
        }

        if (!isset($filters[$this->alias['group_id']]) || !is_numeric($filters[$this->alias['group_id']])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->alias['group_id'])
            );
            return false;
        }

        if (!isset($filters[$this->alias['right_id']]) || !is_numeric($filters[$this->alias['right_id']])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->alias['right_id'])
            );
            return false;
        }

        $result = $this->_storage->update('grouprights', $data, $filters);
        // notify observer
        return $result;
    }

    /**
     *
     *
     * @access public
     * @param array $filters
     * @return
     */
    function revokeGroupRight($filters)
    {
        // sanity checks
        if (!isset($filters[$this->alias['group_id']]) || !is_numeric($filters[$this->alias['group_id']])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->alias['group_id'])
            );
            return false;
        }

        if (isset($filters[$this->alias['right_id']]) && !is_numeric($filters[$this->alias['right_id']])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->alias['right_id'])
            );
            return false;
        }

        $result = $this->_storage->delete('grouprights', $filters);
        // notify observer
        return $result;
    }

    /**
     *
     *
     * @access public
     * @param array $data
     * @return
     */
    function addUserToGroup($data)
    {
        // sanity checks
       if (!isset($data[$this->alias['group_id']]) || !is_numeric($data[$this->alias['group_id']])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => $this->alias['group_id'])
            );
            return false;
        }

        if (!isset($data[$this->alias['perm_user_id']]) || !is_numeric($data[$this->alias['perm_user_id']])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => $this->alias['perm_user_id'])
            );
            return false;
        }

        // check if the userhas already been granted added to that group
        $filters = array(
                       $this->alias['perm_user_id'] => $data[$this->alias['perm_user_id']],
                       $this->alias['group_id']     => $data[$this->alias['group_id']],
                   );
        $count = $this->_storage->selectOne('groupusers', $this->alias['group_id'], $filters, true);
        if ($count > 0) {
            return true;
        }

        $result = $this->_storage->insert('groupusers', $data);
        // notify observer
        return $result;
    }

    /**
     *
     *
     * @access public
     * @param array $filters
     * @return
     */
    function removeUserFromGroup($filters)
    {
        // sanity checks
        if (isset($filters[$this->alias['group_id']]) && !is_numeric($filters[$this->alias['group_id']])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->alias['group_id'])
            );
            return false;
        }

        if (!isset($filters[$this->alias['perm_user_id']]) || !is_numeric($filters[$this->alias['perm_user_id']])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->alias['perm_user_id'])
            );
            return false;
        }

        $result = $this->_storage->delete('groupusers', $filters);
        // notify observer
        return $result;
    }

    /**
     *
     *
     * @access public
     * @param array $filters
     * @return
     */
    function removeRight($filters)
    {
        // sanity checks
        if (!isset($filters[$this->alias['right_id']]) || !is_numeric($filters[$this->alias['right_id']])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->alias['right_id'])
            );
            return false;
        }

        $filter = array($this->alias['right_id'] => $filters[$this->alias['right_id']]);
        $result = $this->_storage->delete('grouprights', $filter);
        if (!$result) {
            return false;
        }

        return parent::removeRight($filters);
    }

    /**
     *
     *
     * @access public
     * @param array $params
     * @return
     */
    function getGroups($params = array())
    {
        $selectable_tables = array('groups', 'groupusers', 'grouprights', 'rights', 'translations');
        $root_table = 'groups';

        $data = $this->_makeGet($params, $root_table, $selectable_tables);

        if (!empty($with) && is_array($data)) {
            foreach($with as $field => $params) {
                foreach($data as $key => $row) {
                    $params['filters'][$field] = $row[$field];
                    $data[$key]['rights'] = $this->getRights($params);
                }
            }
        }
        return $data;
    }

}
?>
