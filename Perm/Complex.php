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
 * DB_Complex permission administration class
 *
 * @package  LiveUser
 * @category authentication
 */

/**
 * Require the parent class definition
 */
require_once 'LiveUser/Admin/Perm/Medium.php';

/**
 * This class provides a set of functions for implementing a user
 * permission management system on live websites. All authorisation
 * backends/containers must be extensions of this base class.
 *
 * @author  Christian Dickmann <dickmann@php.net>
 * @author  Markus Wolff <wolff@21st.de>
 * @author  Matt Scifo <mscifo@php.net>
 * @version $Id$
 * @package LiveUser
 */
class LiveUser_Admin_Perm_Complex extends LiveUser_Admin_Perm_Medium
{
    /**
     * Constructor
     *
     * @access protected
     * @param  mixed      configuration array
     * @return void
     */
    function LiveUser_Perm_Complex(&$confArray)
    {
        $this->LiveUser_Perm_Medium($confArray);
    }

    /**
     *
     *
     * @access public
     * @param array $data
     * @return
     */
    function assignSubGroup($data)
    {
        if (isset($data['group_id']) && isset($data['subgroup_id']) &&
            $data['subgroup_id'] == $data['group_id']
        ) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Parent group id is the same as the subgroup id')
            );
            return false;
        }

        $filter = array('subgroup_id' => $data['subgroup_id']);
        $result = $this->_storage->selectCount('group_subgroups', 'group_id', $filter);
        if ($result === false) {
            return false;
        }

        if ($result == $data['group_id']) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'This child group is already a Parent of this group')
            );
            return false;
        }

        if (!empty($result)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Child group already has a parent group')
            );
            return false;
        }

        $result = $this->_storage->insert('group_subgroups', $data);
        // notify observer
        return $result;
    }

    /**
     * Don't let the function name fool ya, it actually can remove more
     * then one subgroup record at a time via the filters.
     * Most of the time you pass either group_id or subgroup_id or both
     * with the filters to remove one or more record.
     *
     * @access public
     * @param array $filters
     * @return
     */
    function unassignSubGroup($filters)
    {
        $result = $this->_storage->delete('group_subgroups', $filters);
        if ($result === false) {
            return $result;
        }
        // notify observer
        return true;
    }

    /**
     *
     *
     * @access public
     * @param array $data
     * @return
     */
    function implyRight($data)
    {
        if (isset($data['right_id']) && isset($data['implied_righ_id']) &&
            $data['implied_right_id'] == $data['right_id']
        ) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Right id is the same as the implied right id')
            );
            return false;
        }

        $filter = array(
            'implied_right_id' => $data['implied_right_id'],
            'right_id' => $data['right_id']
        );
        $result = $this->_storage->select('one', 'right_implied', 'right_id', $filter);
        if ($result === false) {
            return $result;
        }

        if (!empty($result)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'This implied right is already implied from this right')
            );
            return false;
        }

        $result = $this->_storage->insert('right_implied', $data);
        if ($result === false) {
            return $result;
        }
        // notify observer
        $filter = array('right_id' => $data['right_id']);
        return $this->_updateImpliedStatus($filter);
    }

    /**
     *
     *
     * @access public
     * @param array $filters
     * @return
     */
    function unimplyRight($filters)
    {
         $count = $this->_storage->selectCount('rights_implied', 'right_id', $filters);
         if ($count === false) {
             return false;
         }

         $data = array();
         $data['implied'] = (bool)$count;

        $this->updateRight($data, $filters);
        if ($result === false) {
            return false;
        }

        $this->_storage->delete('right_implied', $filters);
        // notify observer
        return $this->_updateImpliedStatus($filters);
    }

    /**
     *
     *
     * @access public
     * @param array $filters
     * @return
     */
    function removeArea($filters)
    {
        // remove admin areas stuff
        $this->_storage->delete('area_admin_areas', $filters);
        $result = parent::removeArea($filters);
        if ($result === false) {
            return $result;
        }
        return true;
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
        $result = $this->_storage->delete('right_implied', $filters);
        if ($result === false) {
            return false;
        }
        parent::removeRight($filters);
        // notify observer
        return $this->_updateImpliedStatus($filters);
    }

    /**
     *
     *
     * @access public
     * @param array $filters
     * @return
     */
    function removeUser($filters)
    {
        $data = array('owner_user_id' => null);
        $result = $this->updateGroup($data, $filters);
        if ($result === false) {
            return $result;
        }
        // notify observer
        parent::removeUser($filters);
    }

    /**
     * Removes groups, can remove subgroups recursively if
     * option revursive is passed on as true.
     *
     * @access public
     * @param array $filters
     * @return
     */
    function removeGroup($filters)
    {
        if (isset($filters['subgroup_id']) && $filters['recursive']) {
            $filter = array('group_id' => $filters['subgroup_id']);
            $result = $this->_storage->select('col', 'group_subgroups', 'group_id', $filter);
            if ($result == false) {
                return $result;
            }

            !isset($filters['recursive']) ? $filters['recursive'] = false : '';
            foreach ($result as $subGroupId) {
                $filter = array('group_id' => $subGroupId, 'recursive' => $filters['recursive']);
                $res = $this->removeGroup($filter);
                if ($res === false) {
                    return $res;
                }
            }
        }

        $this->_storage->delete('group_subgroups', $filters);
        parent::removeGroup($filters);
    }

    /**
     *
     *
     * @access public
     * @param array $data
     * @return
     */
    function grantUserRight($data)
    {
        $result = parent::grantUserRight($data);
        if ($result === false) {
            return $result;
        }
        $filter = array('right_id' => $data['right_id']);
        $this->_updateLevelStatus($filter);
        // notify observer
        // Job done ...
        return true;
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
        $result = parent::grantGroupRight($data);
        if ($result === false) {
            return $result;
        }
        $filter = array('right_id' => $data['right_id']);
        $this->_updateLevelStatus($filter);
        // notify observer
        // Job done ...
        return true;
    }

    /**
     *
     *
     * @access public
     * @param array $filters
     * @return
     */
    function _updateImpliedStatus($filters)
    {
         $count = $this->_storage->selectCount('right_implied', 'right_id', $filters);
         if ($count === false) {
             return false;
         }

         $data = array('has_implied' => (bool)$count);

        $result = $this->updateRight($data, $filters);
        if ($result === false) {
            return $result;
        }
        // notify observer
        return true;
    }

    /**
     *
     *
     * @access public
     * @param array $filters
     * @return
     */
    function _updateLevelStatus($filters)
    {
         // Add right level filter that will be used to get user and group count.
         $filters['right_level'] = array('op' => '<', 'value' => LIVEUSER_MAX_LEVEL);

         $usercount = $this->_storage->selectOne('userrights', 'right_id', $filters, true);
         if (!$usercount) {
             return false;
         }

         $groupcount = $this->_storage->selectOne('grouprights', 'right_id', $filters, true);
         if (!$groupcount) {
             return false;
         }

        $data = array('has_level' => ($usercount + $groupcount > 0));
        $filter = array('right_id' => $filters['right_id']);
        $this->_storage->update('rights', $data, $filter);
        // notify observer
        return true;
    }

    function getParentGroup()
    {

    }

    function getGroups($params = array())
    {
        return parent::getGroups($params);
    }

    function getRights($params = array())
    {
        return parent::getRights($params);
    }

    function getImpliedRights()
    {

    }

    function getInheritedRights()
    {

    }
}
?>
