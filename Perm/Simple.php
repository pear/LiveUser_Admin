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

/**#@+
 * Section types
 *
 * @var integer
 */
define('LIVEUSER_SECTION_APPLICATION',  1);
define('LIVEUSER_SECTION_AREA',         2);
define('LIVEUSER_SECTION_GROUP',        3);
define('LIVEUSER_SECTION_LANGUAGE',     4);
define('LIVEUSER_SECTION_RIGHT',        5);
/**#@-*/

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
        $this->_storage = LiveUser::storageFactory($confArray, 'LiveUser_Admin_');
        if (is_array($confArray)) {
            foreach ($confArray as $key => $value) {
                if (isset($this->$key)) {
                    $this->$key =& $confArray[$key];
                }
            }
        }
    }

    /**
     *
     *
     * @access public
     * @param array $data
     * @return
     */
    function addUser($data)
    {
        // sanity checks
        if (!isset($data['auth_container_name'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => 'auth_container_name')
            );
            return false;
        }

        if (isset($data['perm_user_id']) && !is_numeric($data['perm_user_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => 'perm_user_id')
            );
            return false;
        }

        if (!isset($data['perm_type'])) {
            $data['perm_type'] = LIVEUSER_USER_TYPE_ID;
        }

        $result = $this->_storage->insert('perm_users', $data);
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
    function updateUser($data, $filters)
    {
        // sanity checks
        if (!isset($filters['perm_user_id']) || !is_numeric($filters['perm_user_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'perm_user_id')
            );
            return false;
        }

        $result = $this->_storage->update('perm_users', $data, $filters);
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

        $result = $this->_storage->delete('perm_users', $filters);
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
    function addRight($data)
    {
        // sanity checks
        if (!isset($data['area_id']) || !is_numeric($data['area_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => 'area_id')
            );
            return false;
        }

        $result = $this->_storage->insert('rights', $data);
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
    function updateRight($data, $filters)
    {
        // sanity checks
        if (!isset($filters['right_id']) || !is_numeric($filters['right_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'right_id')
            );
            return false;
        }

        if (isset($filters['area_id']) && !is_numeric($filters['area_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'area_id')
            );
            return false;
        }

        $result = $this->_storage->update('rights', $data, $filters);
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
        if (!isset($filters['right_id']) || !is_numeric($filters['right_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'right_id')
            );
            return false;
        }
        
        if (isset($filters['area_id']) && !is_numeric($filters['area_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'area_id')
            );
            return false;
        }

        // Remove all user assignments to that right
        $filter_check = array('right_id' => $filters['right_id']);
        $result = $this->_storage->delete('userrights', $filter_check);
        if (!$result) {
            return false;
        }

        $result = $this->_storage->delete('rights', $filters);
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
    function addArea($data)
    {
        // sanity checks
        if (!isset($data['application_id']) || !is_numeric($data['application_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => 'application_id')
            );
            return false;
        }

        $result = $this->_storage->insert('areas', $data);
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
    function updateArea($data, $filters)
    {
        // sanity checks
        if (!isset($filters['area_id']) || !is_numeric($filters['area_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'area_id')
            );
            return false;
        }

        if (isset($data['application_id']) && !is_numeric($data['application_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'application_id')
            );
            return false;
        }

        $result = $this->_storage->update('areas', $data, $filters);
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
        
        // Remove all rights under that area
        $filter_check = array('area_id' => $filters['area_id']);
        $result = $this->_storage->delete('rights', $filter_check);
        if (!$result) {
            return false;
        }

        $result = $this->_storage->delete('areas', $filters);
        // notify observer
        return $result;
    }

    /**
     * Set current application
     *
     * @access public
     * @param  integer  id of application
     * @return boolean  always true
     */
    function setCurrentApplication($applicationId)
    {
        $this->_application = $applicationId;

        return true;
    }

    /**
     * Get current application
     *
     * @access public
     * @return string name of the current application
     */
    function getCurrentApplication()
    {
        return $this->_application;
    }

    /**
     *
     *
     * @access public
     * @param array $data
     * @return
     */
    function addApplication($data)
    {
        // sanity checks
        if (isset($data['application_id']) && !is_numeric($data['application_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => 'application_id')
            );
            return false;
        }

        $result = $this->_storage->insert('applications', $data);
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
    function updateApplication($data, $filters)
    {
        // sanity checks
        if (!isset($filters['application_id']) || !is_numeric($filters['application_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'application_id')
            );
            return false;
        }

        $result = $this->_storage->update('applications', $data, $filters);
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
    function removeApplication($filters)
    {
        // sanity checks
        if (!isset($filters['application_id']) || !is_numeric($filters['application_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'application_id')
            );
            return false;
        }

        // Remove all areas under that application
        $filter_check = array('application_id' => $filters['application_id']);
        $result = $this->_storage->delete('areas', $filter_check);
        if (!$result) {
            return false;
        }

        $result = $this->_storage->delete('applications', $filters);
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
    function grantUserRight($data)
    {
        // sanity checks
        if (!isset($data['perm_user_id']) || !is_numeric($data['perm_user_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => 'perm_user_id')
            );
            return false;
        }

        if (!isset($data['right_id']) || !is_numeric($data['right_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => 'right_id')
            );
            return false;
        }

        if (!isset($data['right_level'])) {
            $data['right_level'] = LIVEUSER_MAX_LEVEL;
        }

        // check if already exists
        $filters = array(
                       'perm_user_id' => $data['perm_user_id'],
                       'right_id'     => $data['right_id'],
                   );
        $count = $this->_storage->selectOne('userrights', 'right_id', $filters, true);
        if ($count > 0) {
            return false;
        }

        $result = $this->_storage->insert('userrights', $data);
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
    function updateUserRight($data, $filters)
    {
        // sanity checks
        if (!isset($data['right_level'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => 'right_level')
            );
            return false;
        }

        if (!isset($filters['perm_user_id']) || !is_numeric($filters['perm_user_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'perm_user_id')
            );
            return false;
        }
        
        if (!isset($filters['right_id']) || !is_numeric($filters['right_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => 'right_id')
            );
            return false;
        }

        $result = $this->_storage->update('userrights', $data, $filters);
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
    function revokeUserRight($filters)
    {
        // sanity checks
        if (!isset($filters['perm_user_id']) || !is_numeric($filters['perm_user_id'])) {
            return false;
        }
        
        if (isset($filters['right_id']) && !is_numeric($filters['right_id'])) {
            return false;
        }

        $result = $this->_storage->delete('userrights', $filters);
        // notify observer
        return $result;
    }

    /**
     *
     *
     * @access private
     * @param array $params
     * @param string $root_table
     * @param array $selectable_tables
     * @return
     */
    function _makeGet($params, $root_table, $selectable_tables)
    {
        $fields = isset($params['fields']) ? $params['fields'] : array();
        $with = isset($params['with']) ? $params['with'] : array();
        $filters = isset($params['filters']) ? $params['filters'] : array();
        $orders = isset($params['orders']) ? $params['orders'] : array();
        $rekey = isset($params['rekey']) ? $params['rekey'] : false;
        $limit = isset($params['limit']) ? $params['limit'] : null;
        $offset = isset($params['offset']) ? $params['offset'] : null;

        // ensure that all $with fields are fetched
        $fields = array_merge($fields, array_keys($with));

        return $this->_storage->selectAll($fields, $filters, $orders, $rekey, $limit, $offset, $root_table, $selectable_tables);
    }

    /**
     *
     *
     * @access public
     * @param array $params
     * @return
     */
    function getUsers($params = array())
    {
        $selectable_tables = array('perm_users', 'userrights', 'rights', 'groupusers');
        $root_table = 'perm_users';

        $data = $this->_makeGet($params, $root_table, $selectable_tables);

        if (!empty($with) && is_array($data)) {
            foreach($with as $field => $params) {
                // this is lame and needs to be made more flexible
                if ($field == 'perm_user_id' || $field == 'group_id') {
                    $method = 'getRights';
                } else {
                    break;
                }
                foreach($data as $key => $row) {
                    $params['filters'][$field] = $row[$field];
                    $data[$key]['rights'] = $this->$method($params);
                }
            }
        }
        return $data;
    }

    /**
     *
     *
     * @access public
     * @param array $params
     * @return
     */
    function getRights($params = array())
    {
        $selectable_tables = array('rights', 'userrights', 'grouprights', 'translations');
        $root_table = 'rights';

        $data = $this->_makeGet($params, $root_table, $selectable_tables);

        if (!empty($with) && is_array($data)) {
            foreach($with as $field => $params) {
                // this is lame and needs to be made more flexible
                if ($field == 'right_id') {
                    $method = 'getUsers';
                } else {
                    break;
                }
                foreach($data as $key => $row) {
                    $params['filters'][$field] = $row[$field];
                    $data[$key]['rights'] = $this->$method($params);
                }

            }
        }
        return $data;
    }

    /**
     *
     *
     * @access public
     * @param array $params
     * @return
     */
    function getAreas($params = array())
    {
        $selectable_tables = array('areas', 'applications', 'translations');
        $root_table = 'areas';

        return $this->_makeGet($params, $root_table, $selectable_tables);
    }

    /**
     *
     *
     * @access public
     * @param array $params
     * @return
     */
    function getApplications($params = array())
    {
        $selectable_tables = array('applications', 'translations');
        $root_table = 'applications';

        return $this->_makeGet($params, $root_table, $selectable_tables);
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