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
require_once 'LiveUser/Admin/Storage/SQL.php';
require_once 'MDB2.php';

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
class LiveUser_Admin_Storage_MDB2 extends LiveUser_Admin_Storage_SQL
{
    var $force_seq = true;

    /**
     * Constructor
     *
     * @access protected
     * @param  mixed      configuration array
     * @return void
     */
    function LiveUser_Admin_Storage_MDB2(&$confArray, &$storageConf)
    {
        $this->LiveUser_Admin_Storage_SQL($confArray, $storageConf);
    }

    function init(&$storageConf)
    {
        if (isset($storageConf['connection']) &&
            MDB2::isConnection($storageConf['connection'])
        ) {
            $this->dbc = &$storageConf['connection'];
        } elseif (isset($storageConf['dsn'])) {
            $this->dsn = $storageConf['dsn'];
            $function = null;
            if (isset($storageConf['function'])) {
                $function = $storageConf['function'];
            }
            $options = null;
            if (isset($storageConf['options'])) {
                $options = $storageConf['options'];
            }
            $options['portability'] = MDB2_PORTABILITY_ALL;
            if ($function == 'singleton') {
                $this->dbc =& MDB2::singleton($storageConf['dsn'], $options);
            } else {
                $this->dbc =& MDB2::connect($storageConf['dsn'], $options);
            }
            if (PEAR::isError($this->dbc)) {
                $this->_stack->push(
                    LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                    array('msg' => 'could not create connection: '.$this->dbc->getMessage())
                );
                return false;
            }
        }
        return true;
    }

    function quote($value, $type)
    {
        return $this->dbc->quote($value, $type);
    }

    function implodeArray($array, $type)
    {
        $this->dbc->loadModule('datatype');
        return $this->dbc->datatype->implodeArray($array, $type);
    }

    function setLimit($limit, $offset)
    {
        if ($limit || $offset) {
            return $this->dbc->setLimit($limit, $offset);
        }
    }

    function query($query)
    {
        return $this->dbc->query($query);
    }

    function queryOne($query, $type)
    {
        return $this->dbc->queryOne($query, $type);
    }

    function queryRow($query, $type)
    {
        return $this->dbc->queryRow($query, $type);
    }

    function queryCol($query, $type)
    {
        return $this->dbc->queryCol($query, $type);
    }

    function queryAll($query, $types, $rekey)
    {
        return $this->dbc->queryAll($query, $types, MDB2_FETCHMODE_ASSOC, $rekey);
    }

    function nextId($seqname, $ondemand = true)
    {
        return $this->dbc->nextId($seqname, $ondemand);
    }

    function getBeforeId($table, $ondemand = true)
    {
        if ($this->force_seq) {
            return $this->dbc->nextId($table, $ondemand);
        }
        return $this->dbc->getBeforeId($table);
    }

    function getAfterId($id, $table)
    {
        if ($this->force_seq) {
            return $id;
        }
        return $this->dbc->getAfterId($id, $table);
    }

    function disconnect()
    {
        return $this->dbc->disconnect();
    }
}
?>
