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
        if (!isset($data[$this->ids[$table]])) {
            $data[$this->ids[$table]] = $this->dbc->nextId($this->prefix . $table, true);
        }
      
        $fields = array();
        $values = array();
        foreach ($data as $field => $value) {
            $fields[] = $field;
            $values[] = $this->quote($value, $this->fields[$field]['type']);
        }
       
        $query = $this->createInsert($table, $fields, $values);
        return $this->dbc->query($query);
    }

    function createInsert($table, $fields, $values)
    {
        $query = 'INSERT INTO ' . $this->prefix . $table . "\n";
        $query .= '(' . implode(', ', $fields) . ')' . "\n";
        $query .= 'VALUES (' . implode(', ', $values) . ')';
        return $query;
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
var_dump('query was not created');
            return false;
        }

        $this->setLimit($limit, $offset);
        return $this->queryAll($query, $types, $rekey);
    }

    function createSelect($fields, $filters, $orders, $root_table, $selectable_tables)
    {
        $tables = $this->findTables($fields, $filters, $orders, $selectable_tables);

        if (!$tables) {
var_dump('no tables were found');
            return false;
        }

        $tables[$root_table] = true;
        $joinfilters = array();
        if (count($tables) > 1) {
            // find join condition
            $joinfilters = array();
            $return = $this->createJoinFilter($root_table, $joinfilters, $tables, $selectable_tables);
            if (!$return) {
var_dump('joins could not be set');
                return false;
            }
            $joinfilters = $return[0];
        }

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

        // find explicit tables
        foreach ($fields_not_yet_linked as $key => $field) {
            if (preg_match('/^(.*)\.(.+)$/', $field, $match)) {
                if (!in_array($match[1], $selectable_tables)) {
var_dump('explicit table does not exist: '.$match[1]);
                    return false;
                }
                $tables[$match[1]] = true;
                unset($fields_not_yet_linked[$key]);
            }
        }

        // find implicit tables
        foreach ($selectable_tables as $table) {
            $current_fields = array_intersect($fields_not_yet_linked, $this->tables[$table]['fields']);
            if (empty($current_fields)) {
                continue;
            }
            foreach ($current_fields as $field) {
                for ($i = 0, $j = count($fields); $i < $j; $i++) {
                    if ($field == $fields[$i]) {
                        $fields[$i] = $this->prefix.$table.'.'.$fields[$i];
                    }
                }
                if (isset($filters[$field])) {
                    $filters[$this->prefix.$table.'.'.$field] = $filters[$field];
                    unset($filters[$field]);
                }
                if (isset($orders[$field])) {
                    $orders[$this->prefix.$table.'.'.$field] = $orders[$field];
                    unset($orders[$field]);
                }
            }
            $fields_not_yet_linked = array_diff($fields_not_yet_linked, $this->tables[$table]['fields']);
            $tables[$table] = true;
            if (empty($fields_not_yet_linked)) {
                break;
            }
        }

        if (!empty($fields_not_yet_linked)) {
var_dump('not all fields could be linked to a table');
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
        if (in_array($root_table, $visited)) {
var_dump('infinite recursion detected: '.$root_table);
            return false;
        }
        $visited[] = $root_table;
        $tables_orig = $tables;
        $direct_matches = array_intersect(array_keys($this->tables[$root_table]['joins']), array_keys($tables));
        foreach ($direct_matches as $table) {
            if (!in_array($table, $selectable_tables)) {
                continue;
            }
            if (isset($tables[$table])) {
                if (is_array($this->tables[$root_table]['joins'][$table])) {
                    foreach ($this->tables[$root_table]['joins'][$table] as $joinsource => $jointarget) {
                        if (isset($this->fields[$joinsource]) && isset($this->fields[$jointarget])) {
                            $filters[$this->prefix.$root_table.'.'.$joinsource] =
                                $this->prefix.$table.'.'.$jointarget;
                        } elseif (isset($this->fields[$jointarget])) {
                            $filters[$this->prefix.$table.'.'.$jointarget] =
                                $this->quote($joinsource, $this->fields[$jointarget]['type']);
                        } elseif (isset($this->fields[$joinsource])) {
                            $filters[$this->prefix.$root_table.'.'.$joinsource] =
                                $this->quote($jointarget, $this->fields[$joinsource]['type']);
                        } else {
var_dump('join structure incorrect, one of the two needs to be a field');
                            return false;
                        }
                    }
                } else {
                    $filters[$this->prefix.$root_table.'.'.$this->tables[$root_table]['joins'][$table]] =
                        $this->prefix.$table.'.'.$this->tables[$root_table]['joins'][$table];
                }
            }
        }
        if (empty($tables)) {
            return array($filters, null);
        }
        foreach ($this->tables[$root_table]['joins'] as $table => $fields) {
            if (!in_array($table, $selectable_tables)) {
                continue;
            }
            $tmp_filters = $filters;
            $tmp_tables = $tables;
            if (is_array($fields)) {
                foreach ($fields as $joinsource => $jointarget) {
                    if (isset($this->fields[$joinsource]) && isset($this->fields[$jointarget])) {
                        $tmp_filters[$this->prefix.$root_table.'.'.$joinsource] =
                            $this->prefix.$table.'.'.$jointarget;
                    } elseif (isset($this->fields[$jointarget])) {
                        $tmp_filters[$this->prefix.$table.'.'.$jointarget] =
                            $this->quote($joinsource, $this->fields[$jointarget]['type']);
                    } elseif (isset($this->fields[$joinsource])) {
                        $tmp_filters[$this->prefix.$root_table.'.'.$joinsource] =
                            $this->quote($jointarget, $this->fields[$joinsource]['type']);
                    } else {
var_dump('join structure incorrect, one of the two needs to be a field');
                        return false;
                    }
                }
            } else {
                $tmp_filters[$this->prefix.$root_table.'.'.$fields] =
                    $this->prefix.$table.'.'.$fields;
            }
            unset($tmp_tables[$table]);
            $return = $this->createJoinFilter($table, $tmp_filters, $tmp_tables, $selectable_tables, $visited);
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