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

/**
 * Require parent class definition.
 */
require_once 'LiveUser/Admin/Storage.php';

/**
 * This is a SQL backend driver for the LiveUser class.
 * A database connection object can be passed to the constructor to reuse an
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
 * @category authentication
 * @package  LiveUser_Admin
 * @author  Lukas Smith <smith@pooteeweet.org>
 * @copyright 2002-2005 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version Release: @package_version@
 * @link http://pear.php.net/LiveUser_Admin
 */
class LiveUser_Admin_Storage_SQL extends LiveUser_Admin_Storage
{
    /**
     * dsn that was connected to
     *
     * @var object
     * @access private
     */
    var $dsn = null;

    /**
     * Database connection object.
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

    /**
     * Insert data into a table
     *
     *
     * @param string $table
     * @param array $data
     * @return mixed false on error, true (or new id) on success
     *
     * @access public
     */
    function insert($table, $data)
    {
        // sanity checks
        $sequence_id = false;
        foreach ($this->tables[$table]['fields'] as $field => $required) {
            if ($required) {
                if ($required === 'seq') {
                    if (!isset($data[$field])) {
                        $result = $this->getBeforeId($this->prefix . $this->alias[$table], true);
                        if ($result === false) {
                            return false;
                        }
                        $data[$field] = $sequence_id = $result;
                    } else {
                        $sequence_id = $data[$field];
                    }
                } elseif (!isset($data[$field])) {
                    $this->_stack->push(
                        LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                        array('reason' => 'field may not be empty: '.$field)
                    );
                    return false;
                }
            }
        }

        $fields = array();
        $values = array();
        foreach ($data as $field => $value) {
            $fields[] = $this->alias[$field];
            $value_quoted = $this->quote($value, $this->fields[$field]);
            if ($value_quoted === false) {
                return false;
            }
            $values[] = $value_quoted;
        }

        $result = $this->query($this->createInsert($table, $fields, $values));
        if ($result === false) {
            return false;
        }
        if ($sequence_id !== false) {
            if (is_numeric($sequence_id)) {
                return $sequence_id;
            }
            return $this->getAfterId($sequence_id, $this->prefix . $this->alias[$table]);
        }
        return $result;
    }

    /**
     * Create the SQL necessary for an insert
     *
     *
     * @param string $table
     * @param array $fields
     * @param array $values
     * @return string SQL insert query
     *
     * @access public
     */
    function createInsert($table, $fields, $values)
    {
        $query = 'INSERT INTO ' . $this->prefix . $this->alias[$table] . "\n";
        $query .= '(' . implode(', ', $fields) . ')' . "\n";
        $query .= 'VALUES (' . implode(', ', $values) . ')';
        return $query;
    }

    /**
     * Update data in a table based given filters
     *
     *
     * @param string $table
     * @param array $data
     * @param array $filteres
     * @return mixed false on error, the affected rows on success
     *
     * @access public
     */
    function update($table, $data, $filters)
    {
        $fields = $values = array();
        foreach ($data as $field => $value) {
            // sanity checks
            if ($this->tables[$table]['fields'][$field]
                && (!array_key_exists($field, $data) || $data[$field] === '')
            ) {
                $this->_stack->push(
                    LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                    array('reason' => 'field may not be empty: '.$field)
                );
                return false;
            }

            $value_quoted = $this->quote($value, $this->fields[$field]);
            if ($value_quoted === false) {
                return false;
            }
            $fields[] = $this->alias[$field] . ' = ' . $value_quoted;
        }

        $result = $this->query($this->createUpdate($table, $fields, $filters));
        return $result;
    }

    /**
     * Create the SQL necessary for an update
     *
     *
     * @param string $table
     * @param array $fields
     * @param array $filters
     * @return string SQL update query
     *
     * @access public
     */
    function createUpdate($table, $fields, $filters)
    {
        $query = 'UPDATE ' . $this->prefix . $this->alias[$table] . ' SET'. "\n";
        $query .= implode(",\n", $fields);
        $query .= $this->createWhere($filters);
        return $query;
    }

    /**
     * Update from a table based given filters
     *
     *
     * @param string $table
     * @param array $filters
     * @return mixed false on error, the affected rows on success
     *
     * @access public
     */
    function delete($table, $filters)
    {
        $fields = $orders = array();
        $selectable_tables = array($table);
        $result = $this->findTables($fields, $filters, $orders, $selectable_tables);
        if ($result === false) {
            return false;
        }
        $query = 'DELETE FROM ' . $this->prefix . $this->alias[$table];
        $query .= $this->createWhere($filters);

        $result = $this->query($query);
        return $result;
    }

    /**
     * Fetches the count of many rows contain the filtered data
     *
     *
     * @param string $table
     * @param string $field
     * @param array $filters
     * @return boolean | integer false on failure and integer of how many 
     *                           rows contain the filtered data
     *
     * @access public
     */
    function selectCount($table, $field, $filters)
    {
        if (empty($field)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => 'field is missing')
            );
            return false;
        }

        if (empty($table)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => 'table is missing')
            );
            return false;
        }

        $query = 'SELECT ';
        $query .= 'COUNT(' . $this->alias[$field] . ')';
        $query .= "\n" . 'FROM ' . $this->prefix . $this->alias[$table];
        $query .= $this->createWhere($filters);
        return $this->queryOne($query, 'integer');
    }

    /**
     * Select data from a set of tables
     *
     *
     * @param array $select
     * @param array $fields
     * @param array $filters
     * @param array $orders
     * @param boolean $rekey
     * @param boolean $group
     * @param integer $limit
     * @param integer $offset
     * @param string $root_table
     * @param array $selectable_tables
     * @return boolean | array false on failure or array with selected data
     *
     * @access public
     */
    function select($select, $fields, $filters, $orders, $rekey, $group, $limit,
        $offset, $root_table, $selectable_tables)
    {
        if (!is_array($fields) || empty($fields)) {
            $fields = array_keys($this->tables[$root_table]['fields']);
        }

        $types = array();
        foreach ($fields as $field) {
            $types[] = $this->fields[$field];
        }

        $query = $this->createSelect($fields, $filters, $orders, $root_table, $selectable_tables);
        if ($query === false) {
            return false;
        }

        $this->setLimit($limit, $offset);

        switch($select) {
        case 'one':
            return $this->queryOne($query, $types);
            break;
        case 'row':
            return $this->queryRow($query, $types);
            break;
        case 'col':
            return $this->queryCol($query, $types);
            break;
        }

        return $this->queryAll($query, $types, $rekey, $group);
    }

    /**
     * Create the SQL necessary for a select
     *
     *
     * @param array $fields
     * @param array $filters
     * @param array $orders
     * @param string $root_table
     * @param array $selectable_tables
     * @return boolean | string false on failure or a string with SQL query
     *
     * @access public
     */
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
            $result = $this->createJoinFilter($root_table, $joinfilters, $tables, $selectable_tables);
            if (!$result) {
                $this->_stack->push(
                    LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                    array('reason' => 'joins could not be set')
                );
                return false;
            }
            $joinfilters = $result[0];
        }

        $tables = array_keys($tables);
        foreach ($tables as $key => $table) {
            $tables[$key] = $this->prefix.$this->alias[$table];
        }
        // build SELECT query
        $query = 'SELECT '.implode(', ', $fields);
        $query.= "\n".' FROM '.implode(', ', $tables);
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

    /**
     * Create the SQL necessary for a where statement
     *
     *
     * @param array $filters
     * @param array $joinfilters
     * @return boolean | string false on failure or string with SQL WHERE
     *
     * @access public
     */
    function createWhere($filters, $joinfilters = array())
    {
        if (empty($filters) && empty($joinfilters)) {
            return '';
        }

        $where = array();

        foreach ($filters as $field => $value) {
            if (array_key_exists($field, $this->fields)) {
                $type = $this->fields[$field];
                $tmp_field = $this->alias[$field];
            // find type for fields with naming like [tablename].[fieldname]
            } elseif (preg_match('/^('.$this->prefix.'[^.]+\.)(.+)$/', $field, $match)
                && array_key_exists($match[2], $this->fields)
            ) {
                $type = $this->fields[$match[2]];
                $tmp_field = $match[1].$this->alias[$match[2]];
            } else {
                $this->_stack->push(
                    LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                    array('reason' => 'field could not be mapped to a type : '.$field)
                );
                return false;
            }

            if (is_array($value)) {
                if (array_key_exists('value', $value)) {
                    if (is_array($value['value'])) {
                        $where[] = $tmp_field.' ' . $value['op'] . ' ('.$this->implodeArray($value['value'], $type).')';
                    } else {
                        $value_quoted = $this->quote($value['value'], $type);
                        if ($value_quoted === false) {
                            return false;
                        }
                        $where[] = $tmp_field. ' ' . $value['op'] . ' ' .$value_quoted;
                    }
                } else {
                    $where[] = $tmp_field.' IN ('.$this->implodeArray($value, $type).')';
                }
            } else {
                $value_quoted = $this->quote($value, $type);
                if ($value_quoted === false) {
                    return false;
                }
                $op = ($value_quoted === 'NULL') ? ' IS ' : ' = ';
                $where[] = $tmp_field.$op.$value_quoted;
            }
        }
        foreach ($joinfilters as $field => $value) {
            $where[] = $field.' = '.$value;
        }
        return "\n".' WHERE '.implode("\n".'     AND ', $where);
    }

    /**
     * Determine if an explicitly prefixed table is in the selectable table
     * list and is a valid field
     *
     *
     * @param array $field
     * @param array $selectable_tables
     * @return mixed | array, null or false on failure
     *
     * @access private
     */
    function _checkExplicitTable($field, $selectable_tables)
    {
        if (!preg_match('/^([^.]+)\.(.+)$/', $field, $match)) {
            return null;
        }
        if (!isset($this->tables[$match[1]]['fields'][$match[2]])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => 'table/field is not defined in the schema structure: '.$field)
            );
            return false;
        }
        if (!in_array($match[1], $selectable_tables)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => 'explicit field is not a selectable: ' . $match[1])
            );
            return false;
        }
        return $match[1];
    }

    /**
     * Find all the tables that need to be joined to be able to select
     * all requested columns and to be able to filter the joined rows
     *
     *
     * @param array &$fields
     * @param array &$filters
     * @param array &$orders
     * @param array $selectable_tables
     * @return boolean | array false on failure
     *
     * @access public
     */
    function findTables(&$fields, &$filters, &$orders, $selectable_tables)
    {
        $tables = array();

        // find tables that the user explicitly requested
        // by using field names like [tablename].[fieldname]
        $fields_tmp = $fields;
        foreach ($fields_tmp as $key => $field) {
           $match = $this->_checkExplicitTable($field, $selectable_tables);
            if (is_null($match)) {
                continue;
            } elseif ($match === false) {
                return false;
            }
            $tables[$match] = true;
            unset($fields_tmp[$key]);
            // append table prefix and AS to this field
            $fields[$key] = $this->prefix.$this->alias[$match[1]].'.'.$match[2].' AS '.$match[2];
        }

        $filters_tmp = $filters;
        foreach ($filters_tmp as $field => $value) {
           $match = $this->_checkExplicitTable($field, $selectable_tables);
            if (is_null($match)) {
                continue;
            } elseif ($match === false) {
                return false;
            }
            $tables[$match] = true;
            unset($fields_tmp[$field]);
            // append prefix to this filter
            $filters[$this->prefix.$this->alias[$match[1]].'.'.$match[2]] = $value;
        }

        $orders_tmp = $orders;
        foreach ($orders_tmp as $field => $value) {
           $match = $this->_checkExplicitTable($field, $selectable_tables);
            if (is_null($match)) {
                continue;
            } elseif ($match === false) {
                return false;
            }
            $tables[$match] = true;
            unset($orders_tmp[$field]);
            // append prefix to this order by field
            $orders[$this->prefix.$this->alias[$match[1]].'.'.$match[2]] = $value;
        }

        $fields_not_yet_linked = array_merge($fields_tmp, array_keys($filters_tmp), array_keys($orders_tmp));
        if (empty($fields_not_yet_linked)) {
            return $tables;
        }

        // find the required tables for all other fields
        $table_prefix = !empty($tables);
        foreach ($selectable_tables as $table) {
            if (!isset($this->tables[$table]['fields'])) {
                $this->_stack->push(
                    LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                    array('reason' => 'table is not defined in the schema structure: '.$table)
                );
                return false;
            }
            // find all fields linked in the current table
            $current_fields = array_intersect($fields_not_yet_linked, array_keys($this->tables[$table]['fields']));
            if (empty($current_fields)) {
                continue;
            }
            // add table to the list of tables to include in the FROM
            $tables[$table] = true;
            // remove fields that have been dealt with
            $fields_not_yet_linked = array_diff($fields_not_yet_linked, $current_fields);
            if ($table_prefix || !empty($fields_not_yet_linked)) {
                $table_prefix = true;
                foreach ($current_fields as $field) {
                    // append table name to all selected fields for this table
                    for ($i = 0, $j = count($fields); $i < $j; $i++) {
                        if ($field == $fields[$i]) {
                            $fields[$i] = $this->prefix.$this->alias[$table].'.'.$this->alias[$fields[$i]].' AS '.$field;
                        }
                    }
                    // append table name to all filter fields for this table
                    // filters are aliased in createWhere
                    if (array_key_exists($field, $filters)) {
                        $filters[$this->prefix.$this->alias[$table].'.'.$field] = $filters[$field];
                        unset($filters[$field]);
                    }
                    // append table name to all order by fields for this table
                    if (array_key_exists($field, $orders)) {
                        $orders[$this->prefix.$this->alias[$table].'.'.$this->alias[$field]] = $orders[$field];
                        unset($orders[$field]);
                    }
                }
            } else {
                foreach ($current_fields as $field) {
                    // alias field
                    for ($i = 0, $j = count($fields); $i < $j; $i++) {
                        if ($field == $fields[$i]) {
                            $fields[$i] = $this->alias[$fields[$i]].' AS '.$field;
                        }
                    }
                    // alias filters
                    // filters are aliased in createWhere
                    // alias orders
                    if (array_key_exists($field, $orders) && $this->alias[$field] != $field) {
                        $orders[$this->alias[$field]] = $orders[$field];
                        unset($orders[$field]);
                    }
                }
            }
            if (empty($fields_not_yet_linked)) {
                break;
            }
        }

        if (!empty($fields_not_yet_linked)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => 'not all fields ('.implode(', ', $fields_not_yet_linked).
                    ') could be linked to a table ('.implode(', ', $selectable_tables).')')
            );
            return false;
        }
        return $tables;
    }

    /**
     * Recursively find all the tables that need to be joined to be able to select
     * all requested columns and to be able to filter the joined rows
     *
     * @param string $root_table
     * @param array $filters
     * @param array $tables
     * @param array $selectable_tables
     * @param array $visited
     * @return boolean | array false on failure
     *
     * @access public
     */
    function createJoinFilter($root_table, $filters, $tables, $selectable_tables, $visited = array())
    {
        // table has been joint
        unset($tables[$root_table]);

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
        $direct_matches = array_intersect(array_keys($this->tables[$root_table]['joins']), $selectable_tables);
        foreach ($direct_matches as $table) {
            // verify that the table is in the selectable_tables list
            if (!array_key_exists($table, $tables)) {
                continue;
            }
            // handle multi column join
            if (is_array($this->tables[$root_table]['joins'][$table])) {
                foreach ($this->tables[$root_table]['joins'][$table] as $joinsource => $jointarget) {
                    // both tables use a field to join
                    if (isset($this->tables[$root_table]['fields'][$joinsource])
                        && isset($this->tables[$table]['fields'][$jointarget])
                    ) {
                        $filters[$this->prefix.$this->alias[$root_table].'.'.$this->alias[$joinsource]] =
                            $this->prefix.$this->alias[$table].'.'.$this->alias[$jointarget];
                    // target table uses a field in the join and source table
                    // a constant value
                    } elseif (isset($this->tables[$table]['fields'][$jointarget])) {
                        $value_quoted = $this->quote($joinsource, $this->fields[$jointarget]);
                        if ($value_quoted === false) {
                            return false;
                        }
                        $filters[$this->prefix.$this->alias[$table].'.'.$this->alias[$jointarget]] = $value_quoted;
                    // source table uses a field in the join and target table
                    // a constant value
                    } elseif (isset($this->tables[$root_table]['fields'][$joinsource])) {
                        $value_quoted = $this->quote($jointarget, $this->fields[$joinsource]);
                        if ($value_quoted === false) {
                            return false;
                        }
                        $filters[$this->prefix.$this->alias[$root_table].'.'.$this->alias[$joinsource]] = $value_quoted;
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
                $filters[$this->prefix.$this->alias[$root_table].'.'.$this->tables[$root_table]['joins'][$table]] =
                    $this->prefix.$this->alias[$table].'.'.$this->tables[$root_table]['joins'][$table];
            }
            $result = $this->createJoinFilter($table, $filters, $tables, $selectable_tables, $visited);
            // check if the recursion was able to find a join that would reduce
            // the number of to be joined tables
            if ($result) {
                if (!$result[1]) {
                    return $result;
                }
                $filters = $result[0];
                $tables = $result[1];
            }
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
                    if (isset($this->tables[$root_table]['fields'][$joinsource])
                        && isset($this->tables[$table]['fields'][$jointarget])
                    ) {
                        $tmp_filters[$this->prefix.$this->alias[$root_table].'.'.$this->alias[$joinsource]] =
                            $this->prefix.$this->alias[$table].'.'.$this->alias[$jointarget];
                    // target table uses a field in the join and source table
                    // a constant value
                    } elseif (isset($this->tables[$table]['fields'][$jointarget])) {
                        $value_quoted = $this->quote($joinsource, $this->fields[$jointarget]);
                        if ($value_quoted === false) {
                            return false;
                        }
                        $tmp_filters[$this->prefix.$this->alias[$table].'.'.$this->alias[$jointarget]] = $value_quoted;
                    // source table uses a field in the join and target table
                    // a constant value
                    } elseif (isset($this->tables[$root_table]['fields'][$joinsource])) {
                        $value_quoted = $this->quote($jointarget, $this->fields[$joinsource]);;
                        if ($value_quoted === false) {
                            return false;
                        }
                        $tmp_filters[$this->prefix.$this->alias[$root_table].'.'.$this->alias[$joinsource]] = $value_quoted;
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
                $tmp_filters[$this->prefix.$this->alias[$root_table].'.'.$fields] =
                    $this->prefix.$this->alias[$table].'.'.$fields;
            }
            // recurse
            $result = $this->createJoinFilter($table, $tmp_filters, $tmp_tables, $selectable_tables, $visited);
            // check if the recursion was able to find a join that would reduce
            // the number of to be joined tables
            if ($result) {
                if (!$result[1]) {
                    return $result;
                }
                $filters = $result[0];
                $tables = $result[1];
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
     * Properly disconnect from database
     *
     * @return void
     *
     * @access public
     */
    function disconnect()
    {
        if ($this->dsn) {
            $result = $this->dbc->disconnect();
            if (PEAR::isError($result)) {
                $this->_stack->push(
                    LIVEUSER_ERROR, 'exception',
                    array('reason' => $result->getMessage() . '-' . $result->getUserInfo())
                );
                return false;
            }
            $this->dbc = null;
        }
        return true;
    }
}
?>
