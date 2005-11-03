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
 * @package  LiveUser_Admin
 * @author  Markus Wolff <wolff@21st.de>
 * @author Helgi Þormar Þorbjörnsson <dufuz@php.net>
 * @author  Lukas Smith <smith@pooteeweet.org>
 * @author Arnaud Limbourg <arnaud@php.net>
 * @author  Christian Dickmann <dickmann@php.net>
 * @author  Matt Scifo <mscifo@php.net>
 * @author  Bjoern Kraus <krausbn@php.net>
 * @copyright 2002-2005 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version CVS: $Id$
 * @link http://pear.php.net/LiveUser_Admin
 */

require_once 'LiveUser/Perm/Simple.php';

/**
 * Simple permission administration class
 *
 * This class provides a set of functions for implementing a user
 * permission management system on live websites. All authorisation
 * backends/containers must be extensions of this base class.
 *
 * @category authentication
 * @package  LiveUser_Admin
 * @author  Markus Wolff <wolff@21st.de>
 * @author  Bjoern Kraus <krausbn@php.net>
 * @author Helgi Þormar Þorbjörnsson <dufuz@php.net>
 * @copyright 2002-2005 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version Release: @package_version@
 * @link http://pear.php.net/LiveUser_Admin
 */
class LiveUser_Admin_Perm_Simple
{
    /**
     * Error stack
     *
     * @var object PEAR_ErrorStack
     * @access private
     */
    var $_stack = null;

    /**
     * Storage Container
     *
     * @var object
     * @access private
     */
    var $_storage = null;

    var $selectable_tables = array(
        'getUsers' => array('perm_users', 'userrights', 'rights'),
        'getRights' => array('rights', 'userrights', 'translations', 'areas', 'applications'),
        'getAreas' => array('areas', 'applications', 'translations'),
        'getApplications' => array('applications', 'translations'),
        'getTranslations' => array('translations'),
    );

    var $withFieldMethodMap = array(
        'perm_user_id' => 'getUsers',
        'right_id' => 'getRights',
        'area_id' => 'getAreas',
        'application_id' => 'getApplications',
    );

    /**
     * Class constructor. Feel free to override in backend subclasses.
     */
    function LiveUser_Admin_Perm_Simple()
    {
        $this->_stack = &PEAR_ErrorStack::singleton('LiveUser_Admin');
    }

    /**
     * Load the storage container
     *
     * @access  public
     * @param  mixed         Name of array containing the configuration.
     * @return  boolean true on success or false on failure
     */
    function init(&$conf)
    {
        if (!array_key_exists('storage', $conf)) {
            $this->_stack->push(LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Missing storage configuration array'));
            return false;
        }

        if (is_array($conf)) {
            $keys = array_keys($conf);
            foreach ($keys as $key) {
                if (isset($this->$key)) {
                    $this->$key =& $conf[$key];
                }
            }
        }

        $this->_storage =& LiveUser::storageFactory($conf['storage'], 'LiveUser_Admin_Perm_');
        if ($this->_storage === false) {
            end($conf['storage']);
            $key = key($conf['storage']);
            $this->_stack->push(LIVEUSER_ERROR, 'exception',
                array('msg' => 'Could not instanciate perm storage container: '.$key));
            return false;
        }

        return true;
    }

    /**
     * Add user
     *
     *
     * @param array $data
     * @return
     *
     * @access public
     */
    function addUser($data)
    {
        if (!array_key_exists('perm_type', $data)) {
            $data['perm_type'] = LIVEUSER_USER_TYPE_ID;
        }

        $result = $this->_storage->insert('perm_users', $data);
        // notify observer
        return $result;
    }

   /**
    * Update user - This will update the liveuser_perm_users table
    *
    *
    * @param array $data    associative array in the form of $fieldname => $data
    * @param array $filters associative array in the form of $fieldname => $data
    *                       This will construct the WHERE clause of your update
    *                       Be careful, if you leave this blank no WHERE clause
    *                       will be used and all users will be affected by the update
    * @return mixed false on error, the affected rows on success
    *
    * @access public
    */
    function updateUser($data, $filters)
    {
        $result = $this->_storage->update('perm_users', $data, $filters);
        // notify observer
        return $result;
    }

    /**
     * Remove user
     *
     *
     * @param array $filters Array containing the filters on what user(s)
     *                       should be removed
     * @return
     *
     * @access public
     * @uses LiveUser_Admin_Perm_Simple::revokeUserRight
     */
    function removeUser($filters)
    {
        $filters = $this->_makeRemoveFilter($filters, 'perm_user_id', 'getUsers');
        if (!$filters) {
            return $filters;
        }

        $result = $this->revokeUserRight($filters);
        if ($result === false) {
            return false;
        }

        $result = $this->_storage->delete('perm_users', $filters);
        // notify observer
        return $result;
    }

    /**
     * Add right
     *
     *
     * @param array $data
     * @return
     *
     * @access public
     */
    function addRight($data)
    {
        $result = $this->_storage->insert('rights', $data);
        // notify observer
        return $result;
    }

   /**
    * Update right - This will update the liveuser_perm_users table
    *
    *
    * @param array $data    associative array in the form of $fieldname => $data
    * @param array $filters associative array in the form of $fieldname => $data
    *                       This will construct the WHERE clause of your update
    *                       Be careful, if you leave this blank no WHERE clause
    *                       will be used and all rights will be affected by the update
    * @return mixed false on error, the affected rows on success
    *
    * @access public
    */ 
    function updateRight($data, $filters)
    {
        $result = $this->_storage->update('rights', $data, $filters);
        // notify observer
        return $result;
    }

    /**
     * Remove right
     *
     *
     * @param array $filters Array containing the filters on what right(s)
     *                       should be removed
     * @return
     *
     * @access public
     * @uses LiveUser_Admin_Perm_Simple::revokeUserRight
     */
    function removeRight($filters)
    {
        $filters = $this->_makeRemoveFilter($filters, 'right_id', 'getRights');
        if (!$filters) {
            return $filters;
        }

        $result = $this->revokeUserRight($filters);
        if ($result === false) {
            return false;
        }

        $result = $this->_storage->delete('rights', $filters);
        // notify observer
        return $result;
    }

    /**
     * Add area
     *
     *
     * @param array $data
     * @return
     *
     * @access public
     */
    function addArea($data)
    {
        $result = $this->_storage->insert('areas', $data);
        // notify observer
        return $result;
    }

   /**
    * Update area - This will update the liveuser_perm_users table
    *
    *
    * @param array $data    associative array in the form of $fieldname => $data
    * @param array $filters associative array in the form of $fieldname => $data
    *                       This will construct the WHERE clause of your update
    *                       Be careful, if you leave this blank no WHERE clause
    *                       will be used and all areas will be affected by the update
    * @return mixed false on error, the affected rows on success
    *
    * @access public
    */ 
    function updateArea($data, $filters)
    {
        $result = $this->_storage->update('areas', $data, $filters);
        // notify observer
        return $result;
    }

    /**
     * Removes all areas define in the filter as well as any
     * rights assigned to that area.
     *
     * <code>
     *  $filters = array(
     *      'area_id' => '34'
     *  );
     *  $foo = $admin->perm->removeArea($filters);
     * </code>
     *
     * Or you can also remove by any other field like so
     *
     * <code>
     *  $filters = array(
     *      'area_define_name' => 'area232'
     *  );
     *  $foo = $admin->perm->removeArea($filters);
     * </code>
     *
     * @param array $filters Array containing the filters on what area(s)
     *                       should be removed
     * @return
     *
     * @access public
     * @uses LiveUser_Admin_Perm_Simple::removeRight
     */
    function removeArea($filters)
    {
        $filters = $this->_makeRemoveFilter($filters, 'area_id', 'getAreas');
        if (!$filters) {
            return $filters;
        }

        $result = $this->removeRight($filters);
        if ($result === false) {
            return false;
        }

        $result = $this->_storage->delete('areas', $filters);
        // notify observer
        return $result;
    }

    /**
     * Set current application
     *
     *
     * @param  integer  id of application
     * @return boolean  always true
     *
     * @access public
     */
    function setCurrentApplication($applicationId)
    {
        $this->_application = $applicationId;

        return true;
    }

    /**
     * Get current application
     *
     *
     * @return string name of the current application
     *
     * @access public
     */
    function getCurrentApplication()
    {
        return $this->_application;
    }

    /**
     * Add application
     *
     *
     * @param array $data
     * @return
     *
     * @access public
     */
    function addApplication($data)
    {
        $result = $this->_storage->insert('applications', $data);
        // notify observer
        return $result;
    }

   /**
    * Update application - This will update the liveuser_perm_users table
    *
    *
    * @param array $data    associative array in the form of $fieldname => $data
    * @param array $filters associative array in the form of $fieldname => $data
    *                       This will construct the WHERE clause of your update
    *                       Be careful, if you leave this blank no WHERE clause
    *                       will be used and all applications will be affected by the update
    * @return mixed false on error, the affected rows on success
    *
    * @access public
    */ 
    function updateApplication($data, $filters)
    {
        $result = $this->_storage->update('applications', $data, $filters);
        // notify observer
        return $result;
    }

    /**
     * Remove application(s)
     *
     *
     * @param array $filters Array containing the filters on what application(s)
     *                       should be removed
     * @return
     *
     * @access public
     * @uses LiveUser_Admin_Perm_Simple::removeArea
     */
    function removeApplication($filters)
    {
        $filters = $this->_makeRemoveFilter($filters, 'application_id', 'getApplications');
        if (!$filters) {
            return $filters;
        }

        $result = $this->removeArea($filters);
        if ($result === false) {
            return false;
        }

        $result = $this->_storage->delete('applications', $filters);
        // notify observer
        return $result;
    }

    /**
     * Grant user a right
     *
     *
     * @param array $data
     * @return
     *
     * @access public
     */
    function grantUserRight($data)
    {
        if (!array_key_exists('right_level', $data)) {
            $data['right_level'] = LIVEUSER_MAX_LEVEL;
        }

        // check if already exists
        $filters = array(
            'perm_user_id' => $data['perm_user_id'],
            'right_id'     => $data['right_id'],
        );
 
        $count = $this->_storage->selectCount('userrights', 'right_id', $filters);
        if ($count > 0) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'This user with perm id '.$data['perm_user_id'].
                    ' has already been granted the right id '.$data['right_id'])
            );
            return false;
        }

        $result = $this->_storage->insert('userrights', $data);
        // notify observer
        return $result;
    }

    /**
     * Update right(s) for the given user(s)
     *
     *
     * @param array $data
     * @param array $filters
     * @return
     *
     * @access public
     */
    function updateUserRight($data, $filters)
    {
        $result = $this->_storage->update('userrights', $data, $filters);
        // notify observer
        return $result;
    }

    /**
     * Revoke (remove) right(s) from the user(s)
     *
     *
     * @param array $filters Array containing the filters on what right(s)
     *                       should be removed from what user(s)
     * @return
     *
     * @access public
     */
    function revokeUserRight($filters)
    {
        $result = $this->_storage->delete('userrights', $filters);
        // notify observer
        return $result;
    }

    /**
     * Add translation
     *
     *
     * @param array $data
     * @return
     *
     * @access public
     */
    function addTranslation($data)
    {
        $result = $this->_storage->insert('translations', $data);
        // notify observer
        return $result;
    }

   /**
    * Update translation - This will update the liveuser_perm_users table
    *
    *
    * @param array $data    associative array in the form of $fieldname => $data
    * @param array $filters associative array in the form of $fieldname => $data
    *                       This will construct the WHERE clause of your update
    *                       Be careful, if you leave this blank no WHERE clause
    *                       will be used and all translations will be affected by the update
    * @return mixed false on error, the affected rows on success
    *
    * @access public
    */ 
    function updateTranslation($data, $filters)
    {
        $result = $this->_storage->update('translations', $data, $filters);
        // notify observer
        return $result;
    }

    /**
     * Remove translation(s)
     *
     * @param array $filters Array containing the filters on what tranlation(s)
     *                       should be removed
     * @return
     *
     * @access public
     */
    function removeTranslation($filters)
    {
        $filters = $this->_makeRemoveFilter($filters, 'translation_id', 'getTranslations');
        if (!$filters) {
            return $filters;
        }

        $result = $this->_storage->delete('translations', $filters);
        // notify observer
        return $result;
    }

    /**
     * Makes the filters used by the remove functions and also
     * checks if there is actually something that needs removing.
     *
     * @param mixed $filteres
     * @param string $key
     * @param string $method
     * @return array
     *
     * @access private
     */
    function _makeRemoveFilter($filters, $key, $method)
    {
        // notes:
        // if all filters apply to the given table only then we can probably
        // skip running the select ..
        // also do we not want to all people to delete the entire contents of
        // a given table?

        if (empty($filters) || !is_array($filters)) {
            return 0;
        }

        if (!isset($filters[$key]) || count($filters) > 1) {
            $params = array(
                'fields' => array($key),
                'filters' => $filters,
                'select' => 'col',
            );
            $result = $this->$method($params);
            if ($result === false) {
                return false;
            }

            if (empty($result)) {
                return 0;
            }

            $filters = array($key => $result);
        }
        return $filters;
    }

    /**
     * This function holds up most of the heat for all the get* functions.
     *
     * @param array $params
     * @param string $root_table
     * @param array $selectable_tables
     * @return
     *
     * @access private
     */
    function _makeGet($params, $root_table, $selectable_tables)
    {
        $fields = array_key_exists('fields', $params) ? $params['fields'] : array();
        $with = array_key_exists('with', $params) ? $params['with'] : array();
        $filters = array_key_exists('filters', $params) ? $params['filters'] : array();
        $orders = array_key_exists('orders', $params) ? $params['orders'] : array();
        $rekey = array_key_exists('rekey', $params) ? $params['rekey'] : false;
        $group = array_key_exists('group', $params) ? $params['group'] : false;
        $limit = array_key_exists('limit', $params) ? $params['limit'] : null;
        $offset = array_key_exists('offset', $params) ? $params['offset'] : null;
        $select = array_key_exists('select', $params) ? $params['select'] : 'all';

        // ensure that all $with fields are fetched
        $fields = array_merge($fields, array_keys($with));

        $data = $this->_storage->select($select, $fields, $filters, $orders,
            $rekey, $group, $limit, $offset, $root_table, $selectable_tables);

        if (!empty($with) && is_array($data)) {
            foreach ($data as $key => $row) {
                foreach ($params['with'] as $field => $with_params) {
                    $with_params['filters'][$field] = $row[$field];
                    $method = $this->withFieldMethodMap[$field];
                    $data_key = preg_replace('/(.+)_id/', '\\1s', $field);
                    $data[$key][$data_key] = $this->$method($with_params);
                }
            }
        }

        return $data;
    }

    /**
     * Fetches users
     *
     *
     * @param array $params
     * @return
     *
     * @access public
     */
    function getUsers($params = array())
    {
        $selectable_tables = $this->selectable_tables['getUsers'];
        $root_table = reset($selectable_tables);

        return $this->_makeGet($params, $root_table, $selectable_tables);
    }

    /**
     * Fetches rights
     *
     *
     * @param array $params
     * @return
     *
     * @access public
     */
    function getRights($params = array())
    {
        $selectable_tables = $this->selectable_tables['getRights'];
        $root_table = reset($selectable_tables);

        return $this->_makeGet($params, $root_table, $selectable_tables);
    }

    /**
     * Fetches areas
     *
     *
     * @param array $params
     * @return
     *
     * @access public
     */
    function getAreas($params = array())
    {
        $selectable_tables = $this->selectable_tables['getAreas'];
        $root_table = reset($selectable_tables);

        return $this->_makeGet($params, $root_table, $selectable_tables);
    }

    /**
     * Fetches applications
     *
     * @access public
     * @param array $params
     * @return
     */
    function getApplications($params = array())
    {
        $selectable_tables = $this->selectable_tables['getApplications'];
        $root_table = reset($selectable_tables);

        return $this->_makeGet($params, $root_table, $selectable_tables);
    }

    /**
     * Fetches translations
     *
     *
     * @param array $params
     * @return
     *
     * @access public
     */
    function getTranslations($params = array())
    {
        $selectable_tables = $this->selectable_tables['getTranslations'];
        $root_table = reset($selectable_tables);

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
     * @param  string  type of output (constant or array)
     * @param  array   options for constants generation
     * @param  string  output mode desired (file or direct)
     * @return mixed   boolean, array or DB Error object
     *
     * @access public
     */
    function outputRightsConstants($type, $options = array(), $mode = null)
    {
        $opt = array();

        $opt['fields'] = array('right_id', 'right_define_name');

        $naming = LIVEUSER_SECTION_RIGHT;
        if (array_key_exists('naming', $options)) {
            $naming = $options['naming'];
            switch ($naming) {
            case LIVEUSER_SECTION_AREA:
                $opt['fields'][] = 'area_define_name';
                break;
            case LIVEUSER_SECTION_APPLICATION:
                $opt['fields'][] = 'application_define_name';
                $opt['fields'][] = 'area_define_name';
                break;
            }
        }

        if (array_key_exists('area', $options)) {
            $opt['filters']['area_id'] = $options['area'];
        }

        if (array_key_exists('application', $options)) {
            $opt['filters']['application_id'] = $options['application'];
        }

        $prefix = '';
        if (array_key_exists('prefix', $options)) {
            $prefix = $options['prefix'] . '_';
        }

        $rekey = false;
        if ($type == 'array' && array_key_exists('rekey', $options)) {
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
                    $app_name = $prefix . $r['application_define_name'];
                    $area_name = $r['area_define_name'];
                    $generate[$app_name][$area_name][$r['right_define_name']] = $r['right_id'];
                }
            } else {
                foreach ($rights as $r) {
                    $key = $prefix . $r['application_define_name'] . '_'
                        . $r['area_define_name'] . '_' . $r['right_define_name'];
                    $generate[$key] = $r['right_id'];
                }
            }
            break;
        case LIVEUSER_SECTION_AREA:
            if ($rekey) {
                foreach ($rights as $r) {
                    $area_name = $prefix . $r['area_define_name'];
                    $generate[$area_name][$r['right_define_name']] = $r['right_id'];
                }
            } else {
                foreach ($rights as $r) {
                    $key = $prefix . $r['area_define_name'] . '_' . $r['right_define_name'];
                    $generate[$key] = $r['right_id'];
                }
            }
            break;
        case LIVEUSER_SECTION_RIGHT:
        default:
            foreach ($rights as $r) {
                $generate[$prefix . $r['right_define_name']] = $r['right_id'];
            }
            break;
        }

        $strDef = "<?php\n";
        if ($type == 'array') {
            if ($mode == 'file') {
                if (!array_key_exists('varname', $options)
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
                    if (!defined($v)) {
                        define($v, $k);
                    }
                }
            }
        }
        $strDef .= '?>';

        if ($mode == 'file') {
            if (!array_key_exists('filename', $options) || !$options['filename']) {
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
