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
        $this->LiveUser_Perm_Simple($confArray);
    }

    function removeArea($filters)
    {
        // sanity checks
        if (!isset($filters['area_id']) || !is_numeric($filters['area_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'area_id')
            );
            return false;
        }

        // remove admin areas stuff
        $this->_storage->delete('area_admin_areas', $filters);
        parent::removeArea($filters);
    }

    function assignSubGroup($data)
    {
        if (!isset($data['group_id']) || !is_numeric($data['group_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'group_id')
            );
            return false;
        }

        if (!isset($data['subgroup_id']) || !is_numeric($data['subgroup_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'subgroup_id')
            );
            return false;
        }

        if ($data['subgroup_id'] == $data['group_id']) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Parent group id is the same as the subgroup id')
            );
            return false;
        }

        $filter = array('subgroup_id' => $filters['subgroup_id']);
        $result = $this->_storage->selectOne('group_subgroups', 'group_id', $filter);
        if (!$result) {
            return $result;
        }

        if (!empty($result)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Child group already has a parent group')
            );
            return false;
        }

        if ($result['group_id'] == $data['group_id']) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'This child group is already a Parent of this group')
            );
            return false;
        }

        $result = $this->_storage->insert('group_subgroups', $data);
        return $reslult;
    }

    function unassignSubGroup($filters)
    {
        if (!isset($filters['subgroup_id']) || !is_numeric($filters['subgroup_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'subgroup_id')
            );
            return false;
        }

        $result = $this->_storage->delete('group_subgroup', $filters);
    }

    function removeGroup($filters)
    {
        if (!isset($filters['group_id']) || !is_numeric($filters['group_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'group_id')
            );
            return false;
        }

        if (!isset($filters['subgroup_id']) || !is_numeric($filters['subgroup_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'subgroup_id')
            );
            return false;
        }

        if ($filters['recursive']) {
            $filter = array('group_id' => $filters['subgroup_id']);
            $result = $this->_storage->selectCol('group_subgroups', 'group_id', $filter);
            if (!$result) {
                return $result;
            }

            foreach ($result as $subGroupId) {
                    $filter = array('group_id' => $subGroupId, 'recursive' => true);
                    $res = $this->removeGroup($filter);
                    if (!$res) {
                        return $res;
                    }
            }
        }

        $this->_storage->delete('group_subgroups', $filters);
        parent::removeGroup($filters);
    }

    function _updateImpliedStatus($filters)
    {
        // sanity checks
        if (!isset($filters['right_id']) || !is_numeric($filters['right_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'right_id')
            );
            return false;
        }

         $count = $this->_storage->selectOne('rights_implied', 'right_id', $filters, true);
         if ($count === false) {
             return false;
         }

         $data = array();
         $data['implied'] = (bool)$count;

        $this->updateRight($data, $filters);
        if (!$result) {
            return false;
        }
        // notify observer
        return true;
    }

    function implyRight($data)
    {
        // sanity checks
        if (!isset($data['right_id']) || !is_numeric($data['right_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'right_id')
            );
            return false;
        }
        
        if (!isset($data['implied_right_id']) || !is_numeric($data['implied_right_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'implied_right_id')
            );
            return false;
        }
        
        $result = $this->_storage->insert('rights_implied', $data);
        if (!$result) {
            return false;
        }
        
        return $this->_updateImpliedStatus($data['right_id']);
    }

    function unimplyRight($filters)
    {
        // sanity checks
        if (!is_numeric($filters['right_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'right_id')
            );
            return false;
        }
        
        if (!is_numeric($filters['implied_right_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'implied_right_id')
            );
            return false;
        }

        $this->_storage->delete('rights_implied', $filters);

        return $this->_updateImpliedStatus($data['right_id']);
    }

    function removeRight($filters)
    {
        // sanity checks
        if (!isset($filters['right_id']) || !is_numeric($filters['right_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'right_id')
            );
            return false;
        }

        $this->_storage->delete('rights_implied', $filters);
        parent::removeRight($filters);
        
        return $this->_updateImpliedStatus($filters);
    }

    function removeUser($filters)
    {
        // sanity checks
        if (!isset($filters['perm_user_id']) || !is_numeric($filters['perm_user_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'perm_user_id')
            );
            return false;
        }

        $data = array('owner_user_id' => 'NULL');
        $result = $this->_storage->update('groups', $data, $filters);
        if (!$result) {
            return false;
        }

        parent::removeUser($filters);
    }

    function grantUserRight($data)
    {
        parent::grantUserRight($data);
        // FIXME
        $this->_updateLevelStatus($data['right_id']);

        // Job done ...
        return true;
    }

    function grantGroupRight($data)
    {
        parent::grantGroupRight($data);
        // FIXME
        $this->_updateLevelStatus($data['right_id']);

        // Job done ...
        return true;
    }

    function _updateImpliedStatus($filters)
    {
        // sanity checks
        if (!isset($filters['right_id']) || !is_numeric($filters['right_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'right_id')
            );
            return false;
        }

         $count = $this->_storage->selectOne('rights_implied', 'right_id', $filters, true);
         if (!$count) {
             return false;
         }

         $data = array();
         $data['implied'] = (int)$count == '0' ? 'Y' : 'N';

        $this->updateRight($data, $filters);
        if (!$result) {
            return false;
        }
        // notify observer
        return true;
    }

    function _updateLevelStatus($filters)
    {
        // sanity checks
        if (!isset($filters['right_id']) || !is_numeric($filters['right_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'right_id')
            );
            return false;
        }

         // Add right level filter that will be used to get user and group count.
         $filters['right_level'] = array('op' => '<', 'value' => LIVEUSER_MAX_LEVEL);

         $usercount = $this->_storage->selectOne('userrights', 'right_id', $filters, true);
         if (!$usercount) {
             return false;
         }

         $grouprcount = $this->_storage->selectOne('grouprights', 'right_id', $filters, true);
         if (!$groupcount) {
             return false;
         }

        $count = $usercount + $groupcount;
        
        $data = array('has_level' => ($count > 0));
        $filter = array('right_id' => $filters['right_id']);
        $this->_storage->update('rights', $data, $filter);
        
        return true;
    }

    function getParentGroup()
    {

    }

    function getGroups()
    {

    }

    function getRights()
    {

    }

    function getImpliedRights()
    {

    }

    function getInheritedRights()
    {

    }
}
?>
