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
        // Remove users from the group
        $result = $this->_storage->delete('groupusers', $filters);
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
        if (!isset($data['right_level'])) {
            $data['right_level'] = LIVEUSER_MAX_LEVEL;
        }

        // check if the group has already been granted that right
        $filters = array(
            'group_id' => $data['group_id'],
            'right_id' => $data['right_id'],
        );
        $count = $this->_storage->selectOne('grouprights', 'right_id', $filters, true);
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
        // check if the userhas already been granted added to that group
        $filters = array(
                       'perm_user_id' => $data['perm_user_id'],
                       'group_id'     => $data['group_id'],
                   );
        $count = $this->_storage->selectOne('groupusers', 'group_id', $filters, true);
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
        $filter = array('right_id' => $filters['right_id']);
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
