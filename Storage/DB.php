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
 * DB_Complex container for permission handling
 *
 * @package  LiveUser
 * @category authentication
 */

/**
 * Require parent class definition.
 */
require_once 'LiveUser/Admin/Storage/SQL.php';
require_once 'DB.php';

/**
 * This is a PEAR::DB backend driver for the LiveUser class.
 * A PEAR::DB connection object can be passed to the constructor to reuse an
 * existing connection. Alternatively, a DSN can be passed to open a new one.
 *
 * Requirements:
 * - File "Liveuser.php" (contains the parent class "LiveUser")
 * - Array of connection options or a PEAR::DB connection object must be
 *   passed to the constructor.
 *   Example: array('dsn' => 'mysql://user:pass@host/db_name')
 *              OR
 *            &$conn (PEAR::DB connection object)
 *
 * @author  Lukas Smith <smith@backendmedia.com>
 * @author  Bjoern Kraus <krausbn@php.net>
 * @version $Id$
 * @package LiveUser
 * @category authentication
 */
class LiveUser_Admin_Storage_DB extends LiveUser_Admin_Storage_SQL
{
    /**
     * Constructor
     *
     * @access protected
     * @param  mixed      configuration array
     * @return void
     */
    function LiveUser_Admin_Storage_DB(&$confArray, &$storageConf)
    {
        $this->LiveUser_Admin_Storage_SQL($confArray, $storageConf);
    }

    function init(&$storageConf)
    {
        if (isset($storageConf['connection']) &&
            DB::isConnection($storageConf['connection'])
        ) {
            $this->dbc = &$storageConf['connection'];
        } elseif (isset($storageConf['dsn'])) {
            $this->dsn = $storageConf['dsn'];
            $options = null;
            if (isset($storageConf['options'])) {
                $options = $storageConf['options'];
            }
            $options['portability'] = DB_PORTABILITY_ALL;
            $this->dbc =& DB::connect($connectOptions['dsn'], $options);
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
        return $this->dbc->quoteSmart($value);
    }

    function implodeArray($array, $type)
    {
        if (!is_array($array) || count($array) == 0) {
            return 'NULL';
        }
        foreach ($array as $value) {
            $return[] = $this->dbc->quoteSmart($value);
        }
        return implode(', ', $return);
    }

    function setLimit($limit, $offset)
    {
        if ($limit || $offset) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                array('msg' => 'limit is not supported by this backend')
            );
            return false;
        }
    }

    function query($query)
    {
        $result = $this->dbc->query($query);
        if (PEAR::isError($result)) {
            return $result;
        }
        return $this->dbc->affectedRows();
    }

    function queryOne($query, $type)
    {
        return $this->dbc->getOne($query);
    }

    function queryRow($query, $type)
    {
        return $this->dbc->getRow($query);
    }

    function queryCol($query, $type)
    {
        return $this->dbc->getCol($query);
    }

    function queryAll($query, $types, $rekey)
    {
        if ($rekey) {
            return $this->dbc->getAssoc($query, false, array(), DB_FETCHMODE_ASSOC);
        } else {
            return $this->dbc->getAll($query, array(), DB_FETCHMODE_ASSOC);
        }
    }

    function nextId($seqname, $ondemand = true)
    {
        return $this->dbc->nextId($seqname, $ondemand);
    }

    function getBeforeId($table, $ondemand = true)
    {
        return $this->dbc->nextId($table, $ondemand);
    }

    function getAfterId($id, $table)
    {
        return $id;
    }

    function disconnect()
    {
        return $this->dbc->disconnect();
    }
}
?>
