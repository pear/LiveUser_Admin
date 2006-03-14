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
 * @package LiveUser_Admin
 * @author  Markus Wolff <wolff@21st.de>
 * @author  Helgi �ormar �orbj�rnsson <dufuz@php.net>
 * @author  Lukas Smith <smith@pooteeweet.org>
 * @author  Arnaud Limbourg <arnaud@php.net>
 * @author  Christian Dickmann <dickmann@php.net>
 * @author  Matt Scifo <mscifo@php.net>
 * @author  Bjoern Kraus <krausbn@php.net>
 * @copyright 2002-2006 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version CVS: $Id$
 * @link http://pear.php.net/LiveUser_Admin
 */

require_once 'LiveUser/Perm/Simple.php';

/**
 * Simple permission administration class that features support for
 * creating, updating, removing and assigning:
 * - users
 * - rights
 * - areas (categorize rights)
 * - applications (categorize areas)
 * - translations (for rights, areas, applications and groups)
 *
 * This class provides a set of functions for implementing a user
 * permission management system on live websites. All authorisation
 * backends/containers must be extensions of this base class.
 *
 * @category authentication
 * @package LiveUser_Admin
 * @author  Markus Wolff <wolff@21st.de>
 * @author  Bjoern Kraus <krausbn@php.net>
 * @author  Helgi �ormar �orbj�rnsson <dufuz@php.net>
 * @copyright 2002-2006 Markus Wolff
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
     * @access public
     */
    var $stack = null;

    /**
     * Storage Container
     *
     * @var object
     * @access private
     */
    var $_storage = null;

    /**
     * Key (method names), with array lists of selectable tables for the given method
     *
     * @var array
     * @access public
     */
    var $selectable_tables = array(
        'getUsers' => array('perm_users', 'userrights', 'rights'),
        'getRights' => array('rights', 'userrights', 'areas', 'applications', 'translations'),
        'getAreas' => array('areas', 'applications', 'translations'),
        'getApplications' => array('applications', 'translations'),
        'getTranslations' => array('translations'),
    );

    /**
     * Key (field name), with method names as values to determine what method
     * should be called to get data when the 'with' option is used in a get*() method
     *
     * @var array
     * @access public
     */
    var $withFieldMethodMap = array(
        'perm_user_id' => 'getUsers',
        'right_id' => 'getRights',
        'area_id' => 'getAreas',
        'application_id' => 'getApplications',
    );

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     */
    function LiveUser_Admin_Perm_Simple()
    {
        $this->stack = &PEAR_ErrorStack::singleton('LiveUser_Admin');
    }

    /**
     * Initialize the storage container
     *
     * @param  array   array containing the configuration.
     * @return bool true on success or false on failure
     *
     * @access  public
     */
    function init(&$conf)
    {
        if (!array_key_exists('storage', $conf)) {
            $this->stack->push(LIVEUSER_ADMIN_ERROR, 'exception',
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
            $this->stack->push(LIVEUSER_ERROR, 'exception',
                array('msg' => 'Could not instanciate perm storage container: '.$key));
            return false;
        }

        return true;
    }

    /**
     * Add a user
     *
     * @param array containing atleast the key-value-pairs of all required
     *              columns in the perm_users table
     * @return int|bool false on error, true (or new id) on success
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
     * Update users
     *
     * @param array containing the key value pairs of columns to update
     * @param array key values pairs (value may be a string or an array)
     *                      This will construct the WHERE clause of your update
     *                      Be careful, if you leave this blank no WHERE clause
     *                      will be used and all users will be affected by the update
     * @return int|bool false on error, the affected rows on success
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
     * Remove users and all their relevant relations
     *
     * @param array key values pairs (value may be a string or an array)
     *                      This will construct the WHERE clause of your update
     *                      Be careful, if you leave this blank no WHERE clause
     *                      will be used and all users will be affected by the removed
     * @return int|bool false on error, the affected rows on success
     *
     * @access public
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
     * Add a right
     *
     * @param array containing atleast the key-value-pairs of all required
     *              columns in the rights table
     * @return int|bool false on error, true (or new id) on success
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
     * Update rights
     *
     * @param array containing the key value pairs of columns to update
     * @param array key values pairs (value may be a string or an array)
     *                      This will construct the WHERE clause of your update
     *                      Be careful, if you leave this blank no WHERE clause
     *                      will be used and all rights will be affected by the update
     * @return int|bool false on error, the affected rows on success
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
     * Remove rights and all their relevant relations
     *
     * @param array key values pairs (value may be a string or an array)
     *                      This will construct the WHERE clause of your update
     *                      Be careful, if you leave this blank no WHERE clause
     *                      will be used and all rights will be affected by the remove
     * @return int|bool false on error, the affected rows on success
     *
     * @access public
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
     * Add an area
     *
     * @param array containing atleast the key-value-pairs of all required
     *              columns in the areas table
     * @return int|bool false on error, true (or new id) on success
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
     * Update areas
     *
     * @param array    associative array in the form of $fieldname => $data
     * @param array associative array in the form of $fieldname => $data
     *                       This will construct the WHERE clause of your update
     *                       Be careful, if you leave this blank no WHERE clause
     *                       will be used and all areas will be affected by the update
     * @return int|bool false on error, the affected rows on success
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
     * Remove areas and all their relevant relations
     *
     * @param array key values pairs (value may be a string or an array)
     *                      This will construct the WHERE clause of your update
     *                      Be careful, if you leave this blank no WHERE clause
     *                      will be used and all areas will be affected by the remove
     * @return int|bool false on error, the affected rows on success
     *
     * @access public
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
     * Add an application
     *
     * @param array containing atleast the key-value-pairs of all required
     *              columns in the applications table
     * @return int|bool false on error, true (or new id) on success
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
     * Update applications
     *
     * @param array containing the key value pairs of columns to update
     * @param array key values pairs (value may be a string or an array)
     *                      This will construct the WHERE clause of your update
     *                      Be careful, if you leave this blank no WHERE clause
     *                      will be used and all applictions will be affected by the update
     * @return int|bool false on error, the affected rows on success
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
     * Remove applications and all their relevant relations
     *
     * @param array key values pairs (value may be a string or an array)
     *                      This will construct the WHERE clause of your update
     *                      Be careful, if you leave this blank no WHERE clause
     *                      will be used and all applications will be affected by the remove
     * @return int|bool false on error, the affected rows on success
     *
     * @access public
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
     * <code>
     * // grant user id 13 the right NEWS_CHANGE
     * $data = array(
     *      'right_id'     => NEWS_CHANGE,
     *      'perm_user_id' => 13
     * );
     * $lua->perm->grantUserRight($data);
     * </code>
     *
     * @param array containing the perm_user_id and right_id and optionally a right_level
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
            $this->stack->push(
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
     * @param array containing the key value pairs of columns to update
     * @param array key values pairs (value may be a string or an array)
     *                      This will construct the WHERE clause of your update
     *                      Be careful, if you leave this blank no WHERE clause
     *                      will be used and all users will be affected by the update
     * @return int|bool false on error, the affected rows on success
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
     * @param array key values pairs (value may be a string or an array)
     *                      This will construct the WHERE clause of your update
     *                      Be careful, if you leave this blank no WHERE clause
     *                      will be used and all users will be affected by the remove
     * @return int|bool false on error, the affected rows on success
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
     * Add a translation
     *
     * @param array containing atleast the key-value-pairs of all required
     *              columns in the users table
     * @return int|bool false on error, true (or new id) on success
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
     * Update translations
     *
     * @param array containing the key value pairs of columns to update
     * @param array key values pairs (value may be a string or an array)
     *                      This will construct the WHERE clause of your update
     *                      Be careful, if you leave this blank no WHERE clause
     *                      will be used and all translations will be affected by the update
     * @return int|bool false on error, the affected rows on success
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
     * Remove translations and all their relevant relations
     *
     * @param array key values pairs (value may be a string or an array)
     *                      This will construct the WHERE clause of your update
     *                      Be careful, if you leave this blank no WHERE clause
     *                      will be used and all translations will be affected by the remove
     * @return int|bool false on error, the affected rows on success
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
     * @param array key values pairs (value may be a string or an array)
     *                      This will construct the WHERE clause of your update
     *                      Be careful, if you leave this blank no WHERE clause
     *                      will be used and all users will be affected by the update
     * @param string name of the column for which we require a filter to be set
     * @param string name of the method that should be used to determine the filter
     * @return int|array|bool 0, an array containing the filter for the key
     *                                  or false on error
     *
     * @access private
     */
    function _makeRemoveFilter($filters, $key, $method)
    {
        // notes:
        // if all filters apply to the given table only then we can probably
        // skip running the select ..
        // also do we not want to allow people to delete the entire contents of
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
     * This function finds the list of selectable tables either from the params
     * or from the selectable_tables property using the method parameter
     *
     * @param string name of the method
     * @param array containing the parameters passed to a get*() method
     * @return array contains the selectable tables
     *
     * @access private
     */
    function _findSelectableTables($method, $params = array())
    {
        $selectable_tables = array();
        if (array_key_exists('selectable_tables', $params)
            && !empty($params['selectable_tables'])
            && is_array($params['selectable_tables'])
        ) {
            $selectable_tables = $params['selectable_tables'];
        } elseif (array_key_exists($method, $this->selectable_tables)) {
            $selectable_tables = $this->selectable_tables[$method];
        }
        return $selectable_tables;
    }

    /**
     * This function holds up most of the heat for all the get* functions.
     *
     * @param array containing key-value pairs for:
     *                 'fields'  - ordered array containing the fields to fetch
     *                             if empty all fields from the user table are fetched
     *                 'filters' - key values pairs (value may be a string or an array)
     *                 'orders'  - key value pairs (values 'ASC' or 'DESC')
     *                 'rekey'   - if set to true, returned array will have the
     *                             first column as its first dimension
     *                 'group'   - if set to true and $rekey is set to true, then
     *                             all values with the same first column will be
     *                             wrapped in an array
     *                 'limit'   - number of rows to select
     *                 'offset'  - first row to select
     *                 'select'  - determines what query method to use:
     *                             'one' -> queryOne, 'row' -> queryRow,
     *                             'col' -> queryCol, 'all' ->queryAll (default)
     * @param string name of the table from which to start looking
     *               for join points
     * @param array list of tables that may be joined to
     * @return bool|array false on failure or array with selected data
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
     * @param array containing key-value pairs for:
     *                 'fields'  - ordered array containing the fields to fetch
     *                             if empty all fields from the user table are fetched
     *                 'filters' - key values pairs (value may be a string or an array)
     *                 'orders'  - key value pairs (values 'ASC' or 'DESC')
     *                 'rekey'   - if set to true, returned array will have the
     *                             first column as its first dimension
     *                 'group'   - if set to true and $rekey is set to true, then
     *                             all values with the same first column will be
     *                             wrapped in an array
     *                 'limit'   - number of rows to select
     *                 'offset'  - first row to select
     *                 'select'  - determines what query method to use:
     *                             'one' -> queryOne, 'row' -> queryRow,
     *                             'col' -> queryCol, 'all' ->queryAll (default)
     *                 'selectable_tables' - array list of tables that may be
     *                             joined to in this query, the first element is
     *                             the root table from which the joins are done
     * @return bool|array false on failure or array with selected data
     *
     * @access public
     */
    function getUsers($params = array())
    {
        $selectable_tables = $this->_findSelectableTables('getUsers' , $params);
        $root_table = reset($selectable_tables);

        return $this->_makeGet($params, $root_table, $selectable_tables);
    }

    /**
     * Fetches rights
     *
     * @param array containing key-value pairs for:
     *                 'fields'  - ordered array containing the fields to fetch
     *                             if empty all fields from the user table are fetched
     *                 'filters' - key values pairs (value may be a string or an array)
     *                 'orders'  - key value pairs (values 'ASC' or 'DESC')
     *                 'rekey'   - if set to true, returned array will have the
     *                             first column as its first dimension
     *                 'group'   - if set to true and $rekey is set to true, then
     *                             all values with the same first column will be
     *                             wrapped in an array
     *                 'limit'   - number of rows to select
     *                 'offset'  - first row to select
     *                 'select'  - determines what query method to use:
     *                             'one' -> queryOne, 'row' -> queryRow,
     *                             'col' -> queryCol, 'all' ->queryAll (default)
     *                 'selectable_tables' - array list of tables that may be
     *                             joined to in this query, the first element is
     *                             the root table from which the joins are done
     * @return bool|array false on failure or array with selected data
     *
     * @access public
     */
    function getRights($params = array())
    {
        $selectable_tables = $this->_findSelectableTables('getRights' , $params);
        $root_table = reset($selectable_tables);

        return $this->_makeGet($params, $root_table, $selectable_tables);
    }

    /**
     * Fetches areas
     *
     * @param array containing key-value pairs for:
     *                 'fields'  - ordered array containing the fields to fetch
     *                             if empty all fields from the user table are fetched
     *                 'filters' - key values pairs (value may be a string or an array)
     *                 'orders'  - key value pairs (values 'ASC' or 'DESC')
     *                 'rekey'   - if set to true, returned array will have the
     *                             first column as its first dimension
     *                 'group'   - if set to true and $rekey is set to true, then
     *                             all values with the same first column will be
     *                             wrapped in an array
     *                 'limit'   - number of rows to select
     *                 'offset'  - first row to select
     *                 'select'  - determines what query method to use:
     *                             'one' -> queryOne, 'row' -> queryRow,
     *                             'col' -> queryCol, 'all' ->queryAll (default)
     *                 'selectable_tables' - array list of tables that may be
     *                             joined to in this query, the first element is
     *                             the root table from which the joins are done
     * @return bool|array false on failure or array with selected data
     *
     * @access public
     */
    function getAreas($params = array())
    {
        $selectable_tables = $this->_findSelectableTables('getAreas' , $params);
        $root_table = reset($selectable_tables);

        return $this->_makeGet($params, $root_table, $selectable_tables);
    }

    /**
     * Fetches applications
     *
     * @param array containing key-value pairs for:
     *                 'fields'  - ordered array containing the fields to fetch
     *                             if empty all fields from the user table are fetched
     *                 'filters' - key values pairs (value may be a string or an array)
     *                 'orders'  - key value pairs (values 'ASC' or 'DESC')
     *                 'rekey'   - if set to true, returned array will have the
     *                             first column as its first dimension
     *                 'group'   - if set to true and $rekey is set to true, then
     *                             all values with the same first column will be
     *                             wrapped in an array
     *                 'limit'   - number of rows to select
     *                 'offset'  - first row to select
     *                 'select'  - determines what query method to use:
     *                             'one' -> queryOne, 'row' -> queryRow,
     *                             'col' -> queryCol, 'all' ->queryAll (default)
     *                 'selectable_tables' - array list of tables that may be
     *                             joined to in this query, the first element is
     *                             the root table from which the joins are done
     * @return bool|array false on failure or array with selected data
     *
     * @access public
     */
    function getApplications($params = array())
    {
        $selectable_tables = $this->_findSelectableTables('getApplications' , $params);
        $root_table = reset($selectable_tables);

        return $this->_makeGet($params, $root_table, $selectable_tables);
    }

    /**
     * Fetches translations
     *
     * @param array containing key-value pairs for:
     *                 'fields'  - ordered array containing the fields to fetch
     *                             if empty all fields from the user table are fetched
     *                 'filters' - key values pairs (value may be a string or an array)
     *                 'orders'  - key value pairs (values 'ASC' or 'DESC')
     *                 'rekey'   - if set to true, returned array will have the
     *                             first column as its first dimension
     *                 'group'   - if set to true and $rekey is set to true, then
     *                             all values with the same first column will be
     *                             wrapped in an array
     *                 'limit'   - number of rows to select
     *                 'offset'  - first row to select
     *                 'select'  - determines what query method to use:
     *                             'one' -> queryOne, 'row' -> queryRow,
     *                             'col' -> queryCol, 'all' ->queryAll (default)
     *                 'selectable_tables' - array list of tables that may be
     *                             joined to in this query, the first element is
     *                             the root table from which the joins are done
     * @return bool|array false on failure or array with selected data
     *
     * @access public
     */
    function getTranslations($params = array())
    {
        $selectable_tables = $this->_findSelectableTables('getTranslations' , $params);
        $root_table = reset($selectable_tables);

        return $this->_makeGet($params, $root_table, $selectable_tables);
    }

    /**
     * Generate the constants to a file or define them directly.
     *
     * $type can be either 'constant' or 'array'. Constant will result in
     * defining constants while array results in defining an array.
     *
     * $options can contain
     * 'prefix'      => 'prefix_goes_here',
     * 'area'        => 'specific area id to grab rights from',
     * 'application' => 'specific application id to grab rights from'
     * 'naming'      => LIVEUSER_SECTION_RIGHT for PREFIX_RIGHTNAME  <- DEFAULT
     *                  LIVEUSER_SECTION_AREA for PREFIX_AREANAME_RIGHTNAME
     *                  LIVEUSER_SECTION_APPLICATION for PREFIX_APPLICATIONNAME_AREANAME_RIGHTNAME
     * 'filename'    => if $mode is 'file' you must give the full path for the
     *                  output file
     * 'varname'     => if $mode is 'file' and $type is 'array' you must give
     *                  the name of the variable to define
     *
     * If no prefix is given it will not be used to generate the constants/arrays
     *
     * $mode can either be 'file' or 'direct' and will determine of the
     * constants/arrays will be written to a file, or returned/defined.
     * returned as an array when $type is set to 'array' and defined when $type
     * is set to 'constant'
     *
     * @param  string  type of output ('constant' or 'array')
     * @param  array   options for constants generation
     * @param  string  output mode desired ('file' or 'direct')
     * @return bool|array depending on the type an array with the data or
     *                       a boolean denoting success or failure
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
                    $this->stack->push(
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
                    $this->stack->push(
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
                $this->stack->push(
                    LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                    array('msg' => 'no filename is set for output mode file')
                );
                return false;
            }

            $fp = @fopen($options['filename'], 'wb');

            if (!$fp) {
                $this->stack->push(
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
