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
                'groupusers' => 'perm_user_id',
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
                'grouprights' => 'right_id',
                'translations' => array(
                    'right_id' => 'section_id',
                    LIVEUSER_SECTION_RIGHT => 'section_type',
                ),
            ),
        ),
        'translations' => array(
            'fields' => array(
                'section_id',
                'section_type',
                'name',
                'description',
            ),
            'joins' => array(
                'rights' => array(
                    'section_id' => 'right_id',
                    'section_type' => LIVEUSER_SECTION_RIGHT,
                ),
                'areas' => array(
                    'section_id' => 'area_id',
                    'section_type' => LIVEUSER_SECTION_AREA,
                ),
                'applications' => array(
                     'section_id' => 'application_id',
                     'section_type' => LIVEUSER_SECTION_APPLICATION,
                ),
                'groups' => array(
                    'section_id' => 'group_id',
                    'section_type' => LIVEUSER_SECTION_GROUP,
                ),
            ),
        ),
        'areas' => array(
            'fields' => array(
                'area_id',
                'application_id',
                'area_define_name',
            ),
            'joins' => array(
                'applications' => 'application_id',
                'translations' => array(
                    'area_id' => 'section_id',
                    LIVEUSER_SECTION_AREA => 'section_type',
                ),
            ),
        ),
        'applications' => array(
            'fields' => array(
                'application_id',
                'application_define_name',
            ),
            'joins' => array(
                'areas' => 'application_id',
                'translations' => array(
                    'application_id' => 'section_id',
                    LIVEUSER_SECTION_APPLICATION => 'section_type',
                ),
            ),
        ),
        'groups' => array(
            'fields' => array(
                'group_id',
                'group_type',
                'group_define_name',
                'is_active',
                'owner_user_id',
                'owner_group_id',
            ),
            'joins' => array(
                'groupusers' => 'group_id',
                'grouprights' => 'group_id',
                'translations' => array(
                    'group_id' => 'section_id',
                    LIVEUSER_SECTION_GROUP => 'section_type',
                ),
            ),
        ),
        'groupusers' => array(
            'fields' => array(
                'perm_user_id',
                'group_id',
            ),
            'joins' => array(
                'groups' => 'group_id',
                'perm_users' => 'perm_user_id',
            ),
        ),
        'grouprights' => array(
            'fields' => array(
                'group_id',
                'right_id',
                'right_level',
            ),
            'joins' => array(
                'rights' => 'right_id',
                'groups' => 'group_id',
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
        'area_id' => array('type' => 'integer'),
        'application_id' => array('type' => 'integer'),
        'right_define_name' => array('type' => 'text'),
        'area_define_name' => array('type' => 'text'),
        'application_define_name' => array('type' => 'text'),
        'section_id' => array('type' => 'integer'),
        'section_type' => array('type' => 'integer'),
        'name' => array('type' => 'text'),
        'description' => array('type' => 'text'),
        'group_id' => array('type' => 'integer'),
        'group_type' => array('type' => 'integer'),
        'group_define_name' => array('type' => 'text'),
        'is_active' => array('type' => 'boolean'),
        'owner_user_id' => array('type' => 'integer'),
        'owner_group_id' => array('type' => 'integer'),
    );

    var $ids = array(
        'rights' => 'right_id',
        'areas' => 'area_id',
        'applications' => 'application_id',
        'perm_users' => 'perm_user_id',
        'translations' => 'translation_id',
        'groups' => 'group_id',
    );

    /**
     * Constructor
     *
     * @access protected
     * @param  mixed      configuration array
     * @return void
     */
    function LiveUser_Admin_Perm_Storage_SQL(&$confArray, &$storageConf)
    {
        $this->LiveUser_Admin_Perm_Storage($confArray, $storageConf);
    }

    function insert($table, $data)
    {
        if (isset($this->ids[$table]) && !isset($data[$this->ids[$table]])) {
            $data[$this->ids[$table]] = $this->dbc->nextId($this->prefix . $table, true);
        }

        $fields = array();
        $values = array();
        foreach ($data as $field => $value) {
            $fields[] = $field;
            $values[] = $this->quote($value, $this->fields[$field]['type']);
        }

        $query = $this->createInsert($table, $fields, $values);
        if (!$query) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => 'query was not created')
            );
            return false;
        }
        return $this->dbc->query($query);
    }

    function createInsert($table, $fields, $values)
    {
        $query = 'INSERT INTO ' . $this->prefix . $table . "\n";
        $query .= '(' . implode(', ', $fields) . ')' . "\n";
        $query .= 'VALUES (' . implode(', ', $values) . ')';
        return $query;
    }

    function update($table, $data, $filters)
    {
        $fields = array();
        $values = array();
        foreach ($data as $field => $value) {
            $fields[] = $field . ' = ' . $this->quote($value, $this->fields[$field]['type']);
        }

        $query = $this->createUpdate($table, $fields, $filters);
        if (!$query) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => 'query was not created')
            );
            return false;
        }
        return $this->dbc->query($query);
    }

    function createUpdate($table, $fields,  $filters)
    {
        $query = 'UPDATE ' . $this->prefix . $table . ' SET'. "\n";
        $query .= implode(",\n", $fields);
        $query .= $this->createWhere($filters);
        return $query;
    }

    function delete($table, $filters)
    {
        $query = 'DELETE FROM ' . $this->prefix . $table;
        $query .= $this->createWhere($filters);
        return $this->dbc->query($query);
    }

    function selectOne($table, $field, $filters, $count = false)
    {
        if (empty($field)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => 'field is missing')
            );
        }

        if (empty($table)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => 'table is missing')
            );
        }

        $query = 'SELECT ';
        if ($count) {
            $query .= 'COUNT(' . $field . ')';
            $type = 'integer';
        } else {
            $query .= $field;
            // find type for fields with naming like [tablename].[fieldname]
            if (preg_match('/^[^.]*\.?(.+)$/', $field, $match)) {
                if (isset($this->fields[$match[1]]['type'])) {
                    $type = $this->fields[$match[1]]['type'];
                }
            } else {
                $type = $this->fields[$field]['type'];
            }
        }
        $query .= "\n" . 'FROM ' . $this->prefix . $table;
        $query .= $this->createWhere($filters);
        return $this->queryOne($query, $type);
    }

    function selectAll($fields, $filters, $orders, $rekey, $limit, $offset, $root_table, $selectable_tables)
    {
        if (!is_array($fields) || empty($fields)) {
            $fields = $this->tables[$root_table]['fields'];
        }

        $types = array();
        foreach ($fields as $field) {
            $types[] = $this->fields[$field]['type'];
        }

        $query = $this->createSelect($fields, $filters, $orders, $root_table, $selectable_tables);
        if (!$query) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => 'query was not created')
            );
            return false;
        }

        $this->setLimit($limit, $offset);
        return $this->queryAll($query, $types, $rekey);
    }

    function createSelect($fields, $filters, $orders, $root_table, $selectable_tables)
    {
        // find the tables to be used inside the query FROM
        $tables = $this->findTables($fields, $filters, $orders, $selectable_tables);

        if (!$tables) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => 'no tables were found')
            );
            return false;
        }

        $tables[$root_table] = true;
        $joinfilters = array();
        if (count($tables) > 1) {
            // find join condition
            $joinfilters = array();
            $return = $this->createJoinFilter($root_table, $joinfilters, $tables, $selectable_tables);
            if (!$return) {
                $this->_stack->push(
                    LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                    array('reason' => 'joins could not be set')
                );
                return false;
            }
            $joinfilters = $return[0];
        }

        // build SELECT query
        $query = 'SELECT '.implode(', ', $fields);
        $query.= "\n".' FROM '.$this->prefix.implode(', '.$this->prefix, array_keys($tables));
        $query.= $this->createWhere($filters, $joinfilters);
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

    function createWhere($filters, $joinfilters = array())
    {
        if (empty($filters)) {
            return '';
        }
        $where = array();
        foreach ($filters as $field => $value) {
            $type = 'text';
            // find type for fields with naming like [tablename].[fieldname]
            if (preg_match('/^[^.]*\.?(.+)$/', $field, $match)) {
                if (isset($this->fields[$match[1]]['type'])) {
                    $type = $this->fields[$match[1]]['type'];
                }
            }
            if (is_array($value)) {
                $where[] = $field.' IN ('.$this->implodeArray($value, $type).')';
            } else {
                $where[] = $field.' = '.$this->quote($value, $type);
            }
        }
        foreach ($joinfilters as $field => $value) {
            $where[] = $field.' = '.$value;
        }
        return "\n".' WHERE '.implode("\n".'     AND ', $where);
    }

    function findTables(&$fields, &$filters, &$orders, $selectable_tables)
    {
        $tables = array();
        $fields_not_yet_linked = array_merge($fields, array_keys($filters), array_keys($orders));

        // find tables that the user explicitly requested by using field names
        // like [tablename].[fieldname]
        foreach ($fields_not_yet_linked as $key => $field) {
            if (preg_match('/^(.*)\.(.+)$/', $field, $match)) {
                if (!in_array($match[1], $selectable_tables)) {
                    $this->_stack->push(
                        LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                        array('reason' => 'explicit table does not exist: ' . $match[1])
                    );
                    return false;
                }
                $tables[$match[1]] = true;
                unset($fields_not_yet_linked[$key]);
            }
        }

        // find the required tables for all other fields
        foreach ($selectable_tables as $table) {
            // find all fields still not linked in the current table
            $current_fields = array_intersect($fields_not_yet_linked, $this->tables[$table]['fields']);
            if (empty($current_fields)) {
                continue;
            }
            // add table to the list of tables to include in the FROM
            $tables[$table] = true;
            foreach ($current_fields as $field) {
                // append table name to all selected fields for this table
                for ($i = 0, $j = count($fields); $i < $j; $i++) {
                    if ($field == $fields[$i]) {
                        $fields[$i] = $this->prefix.$table.'.'.$fields[$i];
                    }
                }
                // append table name to all filter fields for this table
                if (isset($filters[$field])) {
                    $filters[$this->prefix.$table.'.'.$field] = $filters[$field];
                    unset($filters[$field]);
                }
                // append table name to all order by fields for this table
                if (isset($orders[$field])) {
                    $orders[$this->prefix.$table.'.'.$field] = $orders[$field];
                    unset($orders[$field]);
                }
            }
            // remove fields that have been dealt with
            $fields_not_yet_linked = array_diff($fields_not_yet_linked, $current_fields);
            if (empty($fields_not_yet_linked)) {
                break;
            }
        }

        if (!empty($fields_not_yet_linked)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => 'not all fields could be linked to a table')
            );
var_dump($fields_not_yet_linked);
var_dump($tables);
            return false;
        }
        return $tables;
    }

    function createJoinFilter($root_table, $filters, $tables, $selectable_tables, $visited = array())
    {
        if (empty($tables)) {
            return array($filters, null);
        }

        // check for possible infinite recursion
        if (in_array($root_table, $visited)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => 'infinite recursion detected: ' . $root_table)
            );
            return false;
        }
        $visited[] = $root_table;

        $tables_orig = $tables;

        // find tables that can be join directly with the root table
        $direct_matches = array_intersect(array_keys($this->tables[$root_table]['joins']), array_keys($tables));
        foreach ($direct_matches as $table) {
            // verify that the table is in the selectable_tables list
            if (!in_array($table, $selectable_tables)) {
                continue;
            }
            // handle multi column join
            if (is_array($this->tables[$root_table]['joins'][$table])) {
                foreach ($this->tables[$root_table]['joins'][$table] as $joinsource => $jointarget) {
                    // both tables use a field to join
                    if (isset($this->fields[$joinsource]) && isset($this->fields[$jointarget])) {
                        $filters[$this->prefix.$root_table.'.'.$joinsource] =
                            $this->prefix.$table.'.'.$jointarget;
                    // target table uses a field in the join and source table
                    // a constant value
                    } elseif (isset($this->fields[$jointarget])) {
                        $filters[$this->prefix.$table.'.'.$jointarget] =
                            $this->quote($joinsource, $this->fields[$jointarget]['type']);
                    // source table uses a field in the join and target table
                    // a constant value
                    } elseif (isset($this->fields[$joinsource])) {
                        $filters[$this->prefix.$root_table.'.'.$joinsource] =
                            $this->quote($jointarget, $this->fields[$joinsource]['type']);
                    // neither tables uses a field in the join
                    } else {
                        $this->_stack->push(
                            LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                            array('reason' => 'join structure incorrect, one of the two needs to be a field')
                        );
                        return false;
                    }
                }
            // handle single column join
            } else {
                $filters[$this->prefix.$root_table.'.'.$this->tables[$root_table]['joins'][$table]] =
                    $this->prefix.$table.'.'.$this->tables[$root_table]['joins'][$table];
            }
            // table has been joint
            unset($tables[$table]);
        }

        // all tables have been joined
        if (empty($tables)) {
            return array($filters, null);
        }

        foreach ($this->tables[$root_table]['joins'] as $table => $fields) {
            // verify that the table is in the selectable_tables list
            if (!in_array($table, $selectable_tables)) {
                continue;
            }
            $tmp_filters = $filters;
            $tmp_tables = $tables;
            // handle multi column join
            if (is_array($fields)) {
                foreach ($fields as $joinsource => $jointarget) {
                    // both tables use a field to join
                    if (isset($this->fields[$joinsource]) && isset($this->fields[$jointarget])) {
                        $tmp_filters[$this->prefix.$root_table.'.'.$joinsource] =
                            $this->prefix.$table.'.'.$jointarget;
                    // target table uses a field in the join and source table
                    // a constant value
                    } elseif (isset($this->fields[$jointarget])) {
                        $tmp_filters[$this->prefix.$table.'.'.$jointarget] =
                            $this->quote($joinsource, $this->fields[$jointarget]['type']);
                    // source table uses a field in the join and target table
                    // a constant value
                    } elseif (isset($this->fields[$joinsource])) {
                        $tmp_filters[$this->prefix.$root_table.'.'.$joinsource] =
                            $this->quote($jointarget, $this->fields[$joinsource]['type']);
                    // neither tables uses a field in the join
                    } else {
                        $this->_stack->push(
                            LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                            array('reason' => 'join structure incorrect, one of the two needs to be a field')
                        );
                        return false;
                    }
                }
            // handle single column join
            } else {
                $tmp_filters[$this->prefix.$root_table.'.'.$fields] =
                    $this->prefix.$table.'.'.$fields;
            }
            // table has been joint
            unset($tmp_tables[$table]);
            // recurse
            $return = $this->createJoinFilter($table, $tmp_filters, $tmp_tables, $selectable_tables, $visited);
            // check if the recursion was able to find a join that would reduce
            // the number of to be joined tables
            if ($return) {
                if (!$return[1]) {
                    return $return;
                }
                $filters = $return[0];
                $tables = $return[1];
            }
        }

        // return false if list of tables was not reduced using the current root table
        if ($tables_orig == $table) {
            return false;
        }

        // return the generated new filters and reduced table list
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