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
        if (!isset($filters[$this->getAlias('area_id')]) || !is_numeric($filters[$this->getAlias('area_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('area_id'))
            );
            return false;
        }

        // remove admin areas stuff
        $this->_storage->delete('area_admin_areas', $filters);
        parent::removeArea($filters);
    }

    function assignSubGroup($data, $filters)
    {

    }

    function unassignSubGroup($filters)
    {

    }

    function removeGroup($filters)
    {
        parent::removeGroup($filters);
    }

    function _updateImpliedStatus($filters)
    {
        // sanity checks
        if (!isset($filters[$this->getAlias('right_id')]) || !is_numeric($filters[$this->getAlias('right_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('right_id'))
            );
            return false;
        }

         $count = $this->_storage->selectOne('rights_implied', $this->getAlias('right_id'), $filters, true);
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
        if (!isset($data[$this->getAlias('right_id')]) || !is_numeric($data[$this->getAlias('right_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('right_id'))
            );
            return false;
        }
        
        if (!isset($data[$this->getAlias('implied_right_id')]) || !is_numeric($data[$this->getAlias('implied_right_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('implied_right_id'))
            );
            return false;
        }
        
        $result = $this->_storage->insert('rights_implied', $data);
        if (!$result) {
            return false;
        }
        
        return $this->_updateImpliedStatus($data[$this->getAlias('right_id')]);
    }

    function unimplyRight($filters)
    {
        // sanity checks
        if (!is_numeric($filters[$this->getAlias('right_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('right_id'))
            );
            return false;
        }
        
        if (!is_numeric($filters[$this->getAlias('implied_right_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('implied_right_id'))
            );
            return false;
        }

        $this->_storage->delete('rights_implied', $filters);

        return $this->_updateImpliedStatus($data[$this->getAlias('right_id')]);
    }

    function removeRight($filters)
    {
        // sanity checks
        if (!isset($filters[$this->getAlias('right_id')]) || !is_numeric($filters[$this->getAlias('right_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('right_id'))
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
        if (!isset($filters[$this->getAlias('perm_user_id')]) || !is_numeric($filters[$this->getAlias('perm_user_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('perm_user_id'))
            );
            return false;
        }

        $data = array($this->getAlias('owner_user_id') => 'NULL');
        $result = $this->_storage->update('groups', $data, $filters);
        if (!$result) {
            return false;
        }

        parent::removeUser($filters);
    }

    function grantUserRight($data)
    {
        parent::grantUserRight($data);
        $this->_updateLevelStatus($data[$this->getAlias('right_id')]);

        // Job done ...
        return true;
    }

    function grantGroupRight($data)
    {
        parent::grantGroupRight($data);
        $this->_updateLevelStatus($data[$this->getAlias('right_id')]);

        // Job done ...
        return true;
    }

    function _updateImpliedStatus($filters)
    {
        // sanity checks
        if (!isset($filters[$this->getAlias('right_id')]) || !is_numeric($filters[$this->getAlias('right_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('right_id'))
            );
            return false;
        }

         $count = $this->_storage->selectOne('rights_implied', $this->getAlias('right_id'), $filters, true);
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
        if (!isset($filters[$this->getAlias('right_id')]) || !is_numeric($filters[$this->getAlias('right_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('right_id'))
            );
            return false;
        }

         $usercount = $this->_storage->selectOne('userrights', $this->getAlias('right_id'), $filters, true);
         if (!$usercount) {
             return false;
         }

         $grouprcount = $this->_storage->selectOne('grouprights', $this->getAlias('right_id'), $filters, true);
         if (!$groupcount) {
             return false;
         }

        $count = $usercount + $groupcount;
        
        $data = array($this->getAlias('has_level') => ($count > 0));
        $filter = array($this->getAlias('right_id') => $filters[$this->getAlias('right_id')]);
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
