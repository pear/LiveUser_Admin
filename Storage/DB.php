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
 * @author  Lukas Smith <smith@backendmedia.com>
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
 * @category authentication
 * @package  LiveUser_Admin
 * @author  Lukas Smith <smith@backendmedia.com>
 * @author  Bjoern Kraus <krausbn@php.net>
 * @copyright 2002-2005 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version Release: @package_version@
 * @link http://pear.php.net/LiveUser_Admin
 */
class LiveUser_Admin_Storage_DB extends LiveUser_Admin_Storage_SQL
{
    /**
     *
     * @param array &$storageConf Storage Configuration
     * @return
     *
     * @access public
     */
    function init(&$storageConf)
    {
        parent::init($storageConf);

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
            $this->dbc =& DB::connect($storageConf['dsn'], $options);
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
     *
     * @param string $value
     * @param string $type
     * @return string
     *
     * @access public
     * @uses DB::quoteSmart
     */
    function quote($value, $type)
    {
        return $this->dbc->quoteSmart($value);
    }

    /**
     *
     * @param array $array
     * @param string $type
     * @return string
     *
     * @access public
     * @uses DB::quoteSmart
     */
    function implodeArray($array, $type)
    {
        if (!is_array($array) || empty($array)) {
            return 'NULL';
        }
        foreach ($array as $value) {
            $return[] = $this->dbc->quoteSmart($value);
        }
        return implode(', ', $return);
    }

    /**
     * This function is not implemented into DB so we
     * can't make use of it.
     *
     * @param string $limit
     * @param string $offset
     * @return boolean false This feature isn't supported by DB
     *
     * @access public
     */
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

    /**
     *
     * @param string $query
     * @return boolean | integer
     *
     * @access public
     * @uses DB::query DB::affectedRows
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
        return $this->dbc->affectedRows();
    }

    /**
     *
     * @param string $query
     * @param string $type
     * @return boolean | array
     *
     * @access public
     * @uses DB::getOne
     */
    function queryOne($query, $type)
    {
        $result = $this->dbc->getOne($query);
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
     *
     * @param string $query
     * @param string $type
     * @return boolean | array
     *
     * @access public
     * @uses DB::getRow
     */
    function queryRow($query, $type)
    {
        $result = $this->dbc->getRow($query, null, DB_FETCHMODE_ASSOC);
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
     *
     * @param string $query
     * @param string $type
     * @return boolean | array
     *
     * @access public
     * @uses DB::getCol
     */
    function queryCol($query, $type)
    {
        $result = $this->dbc->getCol($query);
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
     *
     * @param string $query
     * @param array $types
     * @param boolean $rekey
     * @return boolean | array
     *
     * @access public
     * @uses DB::getAll DB::getAssoc
     */
    function queryAll($query, $types, $rekey)
    {
        if ($rekey) {
            $result = $this->dbc->getAssoc($query, false, array(), DB_FETCHMODE_ASSOC);
        } else {
            $result = $this->dbc->getAll($query, array(), DB_FETCHMODE_ASSOC);
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
     *
     * @param string $seqname
     * @param boolean $ondemand
     * @return boolean | integer
     *
     * @access public
     * @uses DB::nextId
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
     *
     * @param string $table
     * @param boolean $ondemand
     * @return boolean | integer
     *
     * @access public
     * @uses DB::nextId
     */
    function getBeforeId($table, $ondemand = true)
    {
        $result = $this->dbc->nextId($table, $ondemand);
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
     * getAfterId isn't implemented in DB so we return the $id that
     * was passed by the user
     *
     * @param string $id
     * @param string $table
     * @return integer returns the id that the users passed via params
     *
     * @access public
     */
    function getAfterId($id, $table)
    {
        return $id;
    }

    /**
     *
     * @return mixed false on error or the result
     *
     * @access public
     * @uses DB::disconnect
     */
    function disconnect()
    {
        $result = $this->dbc->disconnect();
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
