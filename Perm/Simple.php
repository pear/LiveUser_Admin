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
 * Base class for permission handling
 *
 * @package  LiveUser
 * @category authentication
 */

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
class LiveUser_Admin_Perm_Simple
{
    /**
     * Class constructor. Feel free to override in backend subclasses.
     */
    function LiveUser_Admin_Perm_Simple(&$confArray)
    {
        $this->_stack = &PEAR_ErrorStack::singleton('LiveUser_Admin');
        $this->_storage = LiveUser::storageFactory($confArray);
        if (is_array($confArray)) {
            foreach ($confArray as $key => $value) {
                if (isset($this->$key)) {
                    $this->$key =& $confArray[$key];
                }
            }
        }
    }

    function addUser($data, $id = null)
    {
        // sanity checks
        $result = $this->_storage->insert('perm_user', $data, $id);
        // notify observer
        return $result;
    }

    function updateUser($data, $id)
    {
        // sanity checks
        $result = $this->_storage->update('perm_user', $data, $filters);
        // notify observer
        return $result;
    }

    function deleteUser($id)
    {
        // sanity checks
        $result = $this->_storage->delete('perm_user', $filters);
        // notify observer
        return $result;
    }

    function getUser($params)
    {
        $selectable_tables = array('perm_users', 'userrights', 'rights');
        $root_table = 'perm_users';

        $fields = isset($params['fields']) ? $params['fields'] : null;
        $filters = isset($params['filters']) ? $params['filters'] : array();
        $orders = isset($params['orders']) ? $params['orders'] : array();
        $rekey = isset($params['rekey']) ? $params['rekey'] : false;
        $limit = isset($params['limit']) ? $params['limit'] : null;
        $offset = isset($params['offset']) ? $params['offset'] : null;

        return $this->_storage->selectAll($fields, $filters, $orders, $rekey, $limit, $offset, $root_table, $selectable_tables);
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
?>