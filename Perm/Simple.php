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
    var $alias = array();

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
     * Get the alias name if available
     */
    function getAlias($name)
    {
        if (isset($this->alias[$name])) {
            return $this->alias[$name];
        }
        return $name;
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
        if (!isset($data[$this->getAlias('auth_container_name')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => $this->getAlias('auth_container_name'))
            );
            return false;
        }

        if (isset($data[$this->getAlias('perm_user_id')]) && !is_numeric($data[$this->getAlias('perm_user_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => $this->getAlias('perm_user_id'))
            );
            return false;
        }

        if (!isset($data[$this->getAlias('perm_type')])) {
            $data[$this->getAlias('perm_type')] = LIVEUSER_USER_TYPE_ID;
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
        if (!isset($filters[$this->getAlias('perm_user_id')]) || !is_numeric($filters[$this->getAlias('perm_user_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('perm_user_id'))
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
        if (!isset($filters[$this->getAlias('perm_user_id')]) || !is_numeric($filters[$this->getAlias('perm_user_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('perm_user_id'))
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
        if (!isset($data[$this->getAlias('area_id')]) || !is_numeric($data[$this->getAlias('area_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => $this->getAlias('area_id'))
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
        if (!isset($filters[$this->getAlias('right_id')]) || !is_numeric($filters[$this->getAlias('right_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('right_id'))
            );
            return false;
        }

        if (isset($filters[$this->getAlias('area_id')]) && !is_numeric($filters[$this->getAlias('area_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('area_id'))
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
        if (!isset($filters[$this->getAlias('right_id')]) || !is_numeric($filters[$this->getAlias('right_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('right_id'))
            );
            return false;
        }

        if (isset($filters[$this->getAlias('area_id')]) && !is_numeric($filters[$this->getAlias('area_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('area_id'))
            );
            return false;
        }

        // Remove all user assignments to that right
        $filter_check = array($this->getAlias('right_id') => $filters[$this->getAlias('right_id')]);
        $result = $this->_storage->delete('userrights', $filter_check);
        if (!$result) {
            return true;
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
        if (!isset($data[$this->getAlias('application_id')]) || !is_numeric($data[$this->getAlias('application_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => $this->getAlias('application_id'))
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
        if (!isset($filters[$this->getAlias('area_id')]) || !is_numeric($filters[$this->getAlias('area_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('area_id'))
            );
            return false;
        }

        if (isset($data[$this->getAlias('application_id')]) && !is_numeric($data[$this->getAlias('application_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('application_id'))
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
        if (!isset($filters[$this->getAlias('area_id')]) || !is_numeric($filters[$this->getAlias('area_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('area_id'))
            );
            return false;
        }

        // Remove all rights under that area
        $filter_check = array($this->getAlias('area_id') => $filters[$this->getAlias('area_id')]);
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
        if (isset($data[$this->getAlias('application_id')]) && !is_numeric($data[$this->getAlias('application_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => $this->getAlias('application_id'))
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
        if (!isset($filters[$this->getAlias('application_id')]) || !is_numeric($filters[$this->getAlias('application_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('application_id'))
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
        if (!isset($filters[$this->getAlias('application_id')]) || !is_numeric($filters[$this->getAlias('application_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('application_id'))
            );
            return false;
        }

        // Remove all areas under that application
        $filter_check = array($this->getAlias('application_id') => $filters[$this->getAlias('application_id')]);
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
        if (!isset($data[$this->getAlias('perm_user_id')]) || !is_numeric($data[$this->getAlias('perm_user_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => $this->getAlias('perm_user_id'))
            );
            return false;
        }

        if (!isset($data[$this->getAlias('right_id')]) || !is_numeric($data[$this->getAlias('right_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => $this->getAlias('right_id'))
            );
            return false;
        }

        if (!isset($data[$this->getAlias('right_level')])) {
            $data[$this->getAlias('right_level')] = LIVEUSER_MAX_LEVEL;
        }

        // check if already exists
        $filters = array(
                       $this->getAlias('perm_user_id') => $data[$this->getAlias('perm_user_id')],
                       $this->getAlias('right_id')     => $data[$this->getAlias('right_id')],
                   );
        $count = $this->_storage->selectOne('userrights', $this->getAlias('right_id'), $filters, true);
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
        if (!isset($data[$this->getAlias('right_level')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => $this->getAlias('right_level'))
            );
            return false;
        }

        if (!isset($filters[$this->getAlias('perm_user_id')]) || !is_numeric($filters[$this->getAlias('perm_user_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('perm_user_id'))
            );
            return false;
        }

        if (!isset($filters[$this->getAlias('right_id')]) || !is_numeric($filters[$this->getAlias('right_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('right_id'))
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
        if (!isset($filters[$this->getAlias('perm_user_id')]) || !is_numeric($filters[$this->getAlias('perm_user_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('perm_user_id'))
            );
            return false;
        }

        if (isset($filters[$this->getAlias('right_id')]) && !is_numeric($filters[$this->getAlias('right_id')])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('key' => $this->getAlias('right_id'))
            );
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

        if (isset($params['with']) && !empty($params['with']) && is_array($data)) {
            foreach($params['with'] as $field => $params) {
                // this is lame and needs to be made more flexible
                if ($field == $this->getAlias('perm_user_id') || $field == $this->getAlias('group_id')) {
                    $method = 'getRights';
                } elseif ($field == $this->getAlias('group_id')) {
                    $method = 'getGroups';
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
        $selectable_tables = array('rights', 'userrights', 'grouprights', 'translations', 'areas', 'applications', 'rights_implied');
        $root_table = 'rights';

        $data = $this->_makeGet($params, $root_table, $selectable_tables);

        if (isset($params['with']) && !empty($params['with']) && is_array($data)) {
            foreach($params['with'] as $field => $params) {
                // this is lame and needs to be made more flexible
                if ($field == $this->getAlias('right_id')) {
                    $method = 'getUsers';
                } elseif ($field == $this->getAlias('group_id')) {
                    $method = 'getGroups';
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
     * Generate the constants to a file or define them directly.
     *
     * $mode can be either 'file' or 'php'. File will write the constant
     * in the given file, replacing/adding constants as needed. Php will
     * call define() function to actually define the constants.
     *
     * $options can contain
     * 'prefix'      => 'prefix_goes_here',
     * 'area'        => 'specific area id to grab rights from',
     * 'application' => 'specific application id to grab rights from'
     * 'naming'      => LIVEUSER_SECTION_RIGHT for PREFIX_RIGHTNAME  <- DEFAULT
     *                  LIVEUSER_SECTION_AREA for PREFIX_AREANAME_RIGHTNAME
     *                  LIVEUSER_SECTION_APPLICATION for PREFIX_APPLICATIONNAME_AREANAME_RIGHTNAME
     * 'filename'    => if $mode is file you must give the full path for the
     *                  output file
     *
     * If not prefix is given it will not be used to generate the constants
     *
     * @access public
     * @param  string  type of output (constant or array)
     * @param  array   options for constants generation
     * @param  string  output mode desired (file or direct)
     * @return mixed   boolean, array or DB Error object
     */
    function outputRightsConstants($type, $options = array(), $mode = null)
    {
        $opt = array();

        $opt['fields'] = array($this->getAlias('right_id'), $this->getAlias('right_define_name'));

        $naming = LIVEUSER_SECTION_RIGHT;
        if (isset($options['naming'])) {
            $naming = $options['naming'];
            switch ($naming) {
            case LIVEUSER_SECTION_AREA:
                $opt['fields'][] = $this->getAlias('area_define_name');
                break;
            case LIVEUSER_SECTION_APPLICATION:
                $opt['fields'][] = $this->getAlias('application_define_name');
                $opt['fields'][] = $this->getAlias('area_define_name');
                break;
            }
        }

        if (isset($options['area'])) {
            $opt['filter'][$this->getAlias('area_id')] = $options['area'];
        }

        if (isset($options['application'])) {
            $opt['filter'][$this->getAlias('application_id')] = $options['application'];
        }

        $prefix = '';
        if (isset($options['prefix'])) {
            $prefix = $options['prefix'] . '_';
        }

        $rekey = false;
        if ($type == 'array' && isset($options['rekey'])) {
            $rekey = $options['rekey'];
        }

        $rights = $this->getRights($opt);

        if ($rights === false) {
            return false;
        }

        $generate = array();

        switch ($naming) {
        case LIVEUSER_SECTION_APPLICATION:
            if ($rekey) {
                foreach ($rights as $r) {
                    $app_name = $prefix . $r[$this->getAlias('application_define_name')];
                    $area_name = $r[$this->getAlias('area_define_name')];
                    $generate[$app_name][$area_name][$r[$this->getAlias('right_define_name')]] = $r[$this->getAlias('right_id')];
                }
            } else {
                foreach ($rights as $r) {
                    $key = $prefix . $r[$this->getAlias('application_define_name')] . '_'
                        . $r[$this->getAlias('area_define_name')] . '_' . $r[$this->getAlias('right_define_name')];
                    $generate[$key] = $r[$this->getAlias('right_id')];
                }
            }
            break;
        case LIVEUSER_SECTION_AREA:
            if ($rekey) {
                foreach ($rights as $r) {
                    $area_name = $prefix . $r[$this->getAlias('area_define_name')];
                    $generate[$area_name][$r[$this->getAlias('right_define_name')]] = $r[$this->getAlias('right_id')];
                }
            } else {
                foreach ($rights as $r) {
                    $key = $prefix . $r[$this->getAlias('area_define_name')] . '_' . $r[$this->getAlias('right_define_name')];
                    $generate[$key] = $r[$this->getAlias('right_id')];
                }
            }
            break;
        case LIVEUSER_SECTION_RIGHT:
        default:
            foreach ($rights as $r) {
                $generate[$prefix . $r[$this->getAlias('right_define_name')]] = $r[$this->getAlias('right_id')];
            }
            break;
        }

        $strDef = "<?php\n";
        if ($type == 'array') {
            if ($mode == 'file') {
                if (!isset($options['varname'])
                    || !preg_match('/^[a-zA-Z_0-9]+$/', $options['varname'])
                ) {
                    $this->_stack->push(
                        LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                        array('msg' => 'varname is not a valid variable name in PHP: '.$options['varname'])
                    );
                    return false;
                }
                $strDef .= sprintf("\$%s = %s;\n", $options['varname'], var_export($generate, true));
            } else {
                return $generate;
            }
        } else {
            foreach ($generate as $v => $k) {
                if (!preg_match('/^[a-zA-Z_0-9]+$/', $v)) {
                    $this->_stack->push(
                        LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                        array('msg' => 'varname is not a valid variable name in PHP: '.$v)
                    );
                    return false;
                }
                $v = strtoupper($v);
                if ($mode == 'file') {
                    $strDef .= sprintf("define('%s', %s);\n", $v, $k);
                } else {
                    if(!defined($v)) {
                        define($v, $k);
                    }
                }
            }
        }
        $strDef .= '?>';

        if ($mode == 'file') {
            if (!isset($options['filename']) || !$options['filename']) {
                $this->_stack->push(
                    LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                    array('msg' => 'no filename is set for output mode file')
                );
                return false;
            }

            $fp = @fopen($options['filename'], 'wb');

            if (!$fp) {
                $this->_stack->push(
                    LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                    array('msg' => 'file could not be opened: '.$options['filename'])
                );
                return false;
            }

            fputs($fp, $strDef);
            fclose($fp);
        }

        return true;
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
