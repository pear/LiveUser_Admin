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
 * MDB2_Complex container for permission handling
 *
 * @package  LiveUser
 * @category authentication
 */

/**
 * Require parent class definition.
 */
require_once 'LiveUser/Admin/Perm/Storage.php';

/**
 * This is a PEAR::MDB2 backend driver for the LiveUser class.
 * A PEAR::MDB2 connection object can be passed to the constructor to reuse an
 * existing connection. Alternatively, a DSN can be passed to open a new one.
 *
 * Requirements:
 * - File "Liveuser.php" (contains the parent class "LiveUser")
 * - Array of connection options or a PEAR::MDB2 connection object must be
 *   passed to the constructor.
 *   Example: array('dsn' => 'mysql://user:pass@host/db_name')
 *              OR
 *            &$conn (PEAR::MDB2 connection object)
 *
 * @author  Lukas Smith <smith@backendmedia.com>
 * @author  Bjoern Kraus <krausbn@php.net>
 * @version $Id$
 * @package LiveUser
 * @category authentication
 */
class LiveUser_Admin_Perm_Storage_SQL extends LiveUser_Admin_Perm_Storage
{
    /**
     * dsn that was connected to
     * @var object
     * @access private
     */
    var $dsn = null;

    /**
     * PEAR::MDB2 connection object.
     *
     * @var    object
     * @access private
     */
    var $dbc = null;

    /**
     * Table prefix
     * Prefix for all db tables the container has.
     *
     * @var    string
     * @access public
     */
    var $prefix = 'liveuser_';

    var $tables = array(
        'perm_users' => array(
            'fields' => array(
                'perm_user_id',
                'auth_user_id',
                'auth_container_name',
                'perm_type',
             ),
            'joins' => array(
                'userrights' => 'perm_user_id',
            ),
        ),
        'userrights' => array(
            'fields' => array(
                'perm_user_id',
                'right_id',
                'right_level',
            ),
            'joins' => array(
                'perm_users' => 'perm_user_id',
                'rights' => 'right_id',
            ),
        ),
        'rights' => array(
            'fields' => array(
                'right_id',
                'area_id',
                'right_define_name',
            ),
            'joins' => array(
                'userrights' => 'right_id',
            ),
        ),
    );

    var $fields = array(
        'perm_user_id' => array('type' => 'integer'),
        'auth_user_id' => array('type' => 'text'),
        'auth_container_name' => array('type' => 'text'),
        'perm_type' => array('type' => 'integer'),
        'right_id' => array('type' => 'integer'),
        'right_level' => array('type' => 'integer'),
        'right_define_name' => array('type' => 'text'),
    );

    /**
     * Constructor
     *
     * @access protected
     * @param  mixed      configuration array
     * @return void
     */
    function LiveUser_Admin_Perm_Storage_MDB2(&$confArray, &$storageConf)
    {
        $this->LiveUser_Admin_Perm_Storage($confArray, $storageConf);
    }

    function selectAll($fields, $filters, $orders, $rekey, $limit, $offset, $root_table, $selectable_tables)
    {
        if (!is_array($fields) || empty($fields)) {
            $fields = $this->tables[$root_table]['fields'];
        }

        $types = array();
        foreach ($fields as $name) {
            $types[] = $this->fields[$name]['type'];
        }

        $query = createSelect($fields, $filters, $orders, $root_table, $selectable_tables);
        if (!$query) {
            return false;
        }

        $this->setLimit($limit, $offset);
        return $this->queryAll($query, $types, $rekey);
    }

    function createSelect($fields, $filters, $orders, $root_table, $selectable_tables)
    {
        $tables = $this->findTables($fields, $filters, $orders, $selectable_tables);

        if (!$tables) {
            return false;
        }

        $tables[$root_table] = true;
        if (count($tables) > 1) {
            // find join condition
            $return = $this->createJoinFilter($root_table, $filters, $tables, $selectable_tables);
            if (!$return) {
                return false;
            }
            $filters = $return[0];
        }

        $query = 'SELECT '.implode(', ', $fields);
        $query.= "\n".' FROM '.$this->prefix.implode(', '.$this->prefix, array_keys($tables));
        $query.= $this->createWhere($filters);
        if ($orders) {
            $query.= "\n".' ORDER BY ';
            $orderby = array();
            foreach ($orders as $name => $direction) {
                $orderby[] = $name.' '.$direction;
            }
            $query.= implode(', ', $orderby);
        }
        return $query;
    }

    function createWhere($filters)
    {
        if (empty($filters)) {
            return '';
        }
        $where = array("\n".' WHERE ');
        foreach ($filters as $name => $value) {
            $type = 'text';
            if (preg_match('/^.*\.?(.+)$/', $name, $match)) {
                if (isset($this->fields[$match[1]]['type'])) {
                    $type = $this->fields[$match[1]]['type'];
                }
            }
            if (is_array($value)) {
                $where[] = $name.' IN ('.$this->implodeArray($value, $type).')';
            } else {
                $where[] = $name.' = '.$this->quote($value, $type);
            }
        }
        return implode("\n".'     AND ', $where);
    }

    function findTables(&$fields, &$filters, &$orders, $selectable_tables)
    {
        $tables = array();
        $fields_not_yet_linked = array_merge($fields, array_values($filters), array_values($orders));

        // find explicit tables
        foreach ($fields_not_yet_linked as $key => $field) {
            if (preg_match('/^(.*)\.(.+)$/', $name, $match)) {
                if (!in_array($match[1], $selectable_tables)) {
                    return false;
                }
                $tables[$match[1]] = true;
                unset($fields_not_yet_linked[$field]);
            }
        }

        // find implicit tables
        foreach ($selectable_tables as $table) {
            $count_not_yet_linked = count($fields_not_yet_linked);
            $current_fields = array_intersect($fields_not_yet_linked, $this->tables[$table]['fields']);
            if (empty($current_fields)) {
                continue;
            }
            foreach ($current_fields as $field) {
                for ($i=0,$j=count($fields); $i<$j; $i++) {
                    if ($field == $fields[$i]) {
                        $fields[$i] = $this->prefix.$table.'.'.$fields[$i];
                    }
                }
                if (!empty($filters)) {
                    $tmp_filters = $filters;
                    foreach($filters as $name => $value) {
                        if ($field == $name) {
                            unset($tmp_filters[$name]);
                            $tmp_filters[$this->prefix.$table.'.'.$name] = $value;
                        }
                    }
                    $filters = $tmp_filters;
                }
                if (!empty($orders)) {
                    $tmp_orders = $orders;
                    foreach($orders as $name => $value) {
                        if ($field == $name) {
                            unset($tmp_orders[$name]);
                            $tmp_orders[$this->prefix.$table.'.'.$name] = $value;
                        }
                    }
                    $orders = $tmp_orders;
                }
            }
            $fields_not_yet_linked = array_diff($fields_not_yet_linked, $this->tables[$table]['fields']);
            if ($count_not_yet_linked > count($fields_not_yet_linked)) {
                $tables[$table] = true;
            }
            if (empty($fields_not_yet_linked)) {
                break;
            }
        }

        if (!empty($fields_not_yet_linked)) {
            return false;
        }
        return $tables;
    }

    function createJoinFilter($root_table, $filters, $tables)
    {
        if (empty($tables)) {
            return array($filters, null);
        }
        $tables_orig = $tables;
        $direct_matches = array_intersect(array_keys($this->tables[$root_table]['joins']), array_keys($tables));
        foreach ($direct_matches as $table) {
            if (isset($tables[$table])) {
                $filters[$this->prefix.$root_table.'.'.$this->tables[$root_table]['joins'][$table]] =
                    ' = '.$this->prefix.$table.'.'.$this->tables[$table]['joins'][$root_table];
                unset($tables[$table]);
            }
        }
        if (empty($tables)) {
            return array($filters, null);
        }
        foreach ($this->tables[$root_table]['joins'] as $table => $field) {
            $tmp_filters = $filters;
            $tmp_tables = $tables;
            if (is_array($this->tables[$root_table]['joins'][$table])) {
                foreach ($this->tables[$root_table]['joins'][$table] as $joinfield) {
                    if (isset($this->fields[$this->tables[$table]['joins'][$root_table]])) {
                        $filter = ' = '.$this->prefix.$table.'.'.$this->tables[$table]['joins'][$root_table];
                    } else {
                        $filter = ' = '.$this->quote($this->tables[$table]['joins'][$root_table], $this->fields[$joinfield]['type']);
                    }
                    $tmp_filters[$this->prefix.$root_table.'.'.$joinfield] = $filter;
                }
            } else {
                $tmp_filters[$this->prefix.$root_table.'.'.$this->tables[$root_table]['joins'][$table]] =
                    ' = '.$this->prefix.$table.'.'.$this->tables[$table]['joins'][$root_table];
            }
            unset($tmp_tables[$table]);
            $return = $this->createJoinFilter($table, $tmp_filters, $tmp_tables);
            if ($return) {
                if (!$return[1]) {
                    return $return;
                }
                $filters = $return[0];
                $tables = $return[1];
            }
        }
        if ($tables_orig == $table) {
            return false;
        }
        return array($filters, $tables);
    }

    /**
     * properly disconnect from resources
     *
     * @access  public
     */
    function disconnect()
    {
        $this->dbc->disconnect();
    }
}
?>