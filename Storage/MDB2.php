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
 * @category authentication
 * @package  LiveUser_Admin
 * @author  Lukas Smith <smith@pooteeweet.org>
 * @author  Bjoern Kraus <krausbn@php.net>
 * @copyright 2002-2005 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version Release: @package_version@
 * @link http://pear.php.net/LiveUser_Admin
 */
class LiveUser_Admin_Storage_MDB2 extends LiveUser_Admin_Storage_SQL
{
    var $force_seq = true;

    /**
     * Initializes database storage container.
     * Connects to database or uses existing database connection.
     *
     * @param array &$storageConf Storage Configuration
     * @return boolean false on failure and true on success
     *
     * @access public
     * @uses LiveUser_Admin_Storage_SQL::init
     */
    function init(&$storageConf)
    {
        parent::init($storageConf);

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

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param string $type type to which the value should be converted to
     * @return stringtext string that represents the given argument value in
     *       a DBMS specific format.
     *
     * @access public
     * @uses MDB2::quote
     */
    function quote($value, $type)
    {
        return $this->dbc->quote($value, $type);
    }

    /**
     * Apply a type to all values of an array and return as a comma
     * seperated string useful for generating IN statements
     *
     * @param array $array data array
     * @param string $type determines type of the field
     *
     * @return string comma seperated values
     *
     * @access public
     * @uses MDB2::implodeArray
     */
    function implodeArray($array, $type)
    {
        $this->dbc->loadModule('datatype');
        return $this->dbc->datatype->implodeArray($array, $type);
    }

    /**
     *  Sets the range of the next query
     *
     * @param string $limit number of rows to select
     * @param string $offset first row to select
     * @return
     *
     * @access public
     * @uses MDB2::setLimit
     */
    function setLimit($limit, $offset)
    {
        if ($limit || $offset) {
            return $this->dbc->setLimit($limit, $offset);
        }
    }

    /**
     * Execute query
     *
     * @param string $query query
     * @return boolean | integer
     *
     * @access public
     * @uses MDB::query
     */
    function query($query)
    {
        $result = $this->dbc->query($query);
        if (PEAR::isError($result)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => $result->getMessage() . '-' . $result->getUserInfo())
            );
            return false;
        }
        return $result;
    }

    /**
     * Execute the specified query, fetch the value from the first column of
     * the first row of the result set and then frees
     * the result set.
     *
     * @param string $query the SELECT query statement to be executed.
     * @param string $type argument that specifies the expected
     *       datatype of the result set field, so that an eventual conversion
     *       may be performed. The default datatype is text, meaning that no
     *       conversion is performed
     * @return boolean | array
     *
     * @access public
     * @uses MDB2::queryOne
     */
    function queryOne($query, $type)
    {
        $result = $this->dbc->queryOne($query, $type);
        if (PEAR::isError($result)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => $result->getMessage() . '-' . $result->getUserInfo())
            );
            return false;
        }
        return $result;
    }

    /**
     * Execute the specified query, fetch the values from the first
     * row of the result set into an array and then frees
     * the result set.
     *
     * @param string $query the SELECT query statement to be executed.
     * @param array $type array argument that specifies a list of
     *       expected datatypes of the result set columns, so that the eventual
     *       conversions may be performed. The default list of datatypes is
     *       empty, meaning that no conversion is performed.
     * @return boolean | array
     *
     * @access public
     * @uses MDB2::queryRow
     */
    function queryRow($query, $type)
    {
        $result = $this->dbc->queryRow($query, $type, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($result)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => $result->getMessage() . '-' . $result->getUserInfo())
            );
            return false;
        }
        return $result;
    }

    /**
     * Execute the specified query, fetch the value from the first column of
     * each row of the result set into an array and then frees the result set.
     *
     * @param string $query the SELECT query statement to be executed.
     * @param string $type argument that specifies the expected
     *       datatype of the result set field, so that an eventual conversion
     *       may be performed. The default datatype is text, meaning that no
     *       conversion is performed
     * @return boolean | array
     *
     * @access public
     * @uses MDB2::queryCol
     */
    function queryCol($query, $type)
    {
        $result = $this->dbc->queryCol($query, $type);
        if (PEAR::isError($result)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => $result->getMessage() . '-' . $result->getUserInfo())
            );
            return false;
        }
        return $result;
    }

    /**
     * Execute the specified query, fetch all the rows of the result set into
     * a two dimensional array and then frees the result set.
     *
     * @param string $query the SELECT query statement to be executed.
     * @param array $types array argument that specifies a list of
     *       expected datatypes of the result set columns, so that the eventual
     *       conversions may be performed. The default list of datatypes is
     *       empty, meaning that no conversion is performed.
     * @param boolean $rekey if set to true, the $all will have the first
     *       column as its first dimension
     * @param boolean $group if set to true and $rekey is set to true, then
     *      all values with the same first column will be wrapped in an array
     * @return boolean | array
     *
     * @access public
     * @uses MDB2::queryAll
     */
    function queryAll($query, $types, $rekey, $group)
    {
        $result = $this->dbc->queryAll($query, $types, MDB2_FETCHMODE_ASSOC, $rekey, false, $group);
        if (PEAR::isError($result)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => $result->getMessage() . '-' . $result->getUserInfo())
            );
            return false;
        }
        return $result;
    }

    /**
     * returns the next free id of a sequence
     *
     * @param string $seqname name of the sequence
     * @param boolean $ondemand when true the seqence is
     *                           automatic created, if it not exists
     * @return boolean | integer
     *
     * @access public
     * @uses MDB2::nextId
     */
    function nextId($seqname, $ondemand = true)
    {
        $result = $this->dbc->nextId($seqname, $ondemand);
        if (PEAR::isError($result)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => $result->getMessage() . '-' . $result->getUserInfo())
            );
            return false;
        }
        return $result;
    }

    /**
     * returns the next free id of a sequence if the RDBMS
     * does not support auto increment
     *
     * @param string $table name of the table into which a new row was inserted
     * @param boolean $ondemand when true the seqence is
     *                          automatic created, if it not exists
     * @return boolean | integer
     *
     * @access public
     * @uses MDB2::nextId MDB2::getBeforeId
     */
    function getBeforeId($table, $ondemand = true)
    {
        if ($this->force_seq) {
            $result = $this->dbc->nextId($table, $ondemand);
        } else {
            $result = $this->dbc->getBeforeId($table);
        }

        if (PEAR::isError($result)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => $result->getMessage() . '-' . $result->getUserInfo())
            );
            return false;
        }
        return $result;
    }

    /**
     * returns the autoincrement ID if supported or $id
     *
     * @param string $id value as returned by getBeforeId()
     * @param string $table name of the table into which a new row was inserted
     * @return  boolean | integer returns the id that the users passed via params
     *
     * @access public
     * @uses MDB2::getAfterId
     */
    function getAfterId($id, $table)
    {
        if ($this->force_seq) {
            return $id;
        }

        $result = $this->dbc->getAfterId($id, $table);
        if (PEAR::isError($result)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_QUERY_BUILDER, 'exception',
                array('reason' => $result->getMessage() . '-' . $result->getUserInfo())
            );
            return false;
        }
        return $result;
    }
}
?>
