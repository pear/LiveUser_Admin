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
        // remove admin areas stuff
        $this->_storage->delete('area_admin_areas', $filters);
        parent::removeArea();
    }
    
    function assignSubGroup()
    {
    
    }
    
    function unassignSubGroup()
    {
    
    }
    
    function removeGroup()
    {
    
    }
    
    function getParentGroup()
    {
    
    }
    
    function _updateImpliedStatus()
    {
    
    }
    
    function implyRight()
    {
    
    }
    
    function unimplyRight()
    {
    
    }
    
    function removeRight()
    {
        parent::removeRight();
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
    
    function removeUser()
    {
        parent::removeUser();
    }
    
    function _updateLevelStatus()
    {
    
    }
    
    function grantUserRight()
    {
    
    }
    
    function grantGroupRight()
    {
    
    }    
}
?>
