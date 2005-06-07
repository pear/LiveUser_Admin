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
require_once 'LiveUser/Admin/Storage/MDB2.php';
require_once 'LiveUser/Auth/Storage/Globals.php';

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
 * @author  Lukas Smith <smith@backendmedia.com>
 * @author  Bjoern Kraus <krausbn@php.net>
 * @copyright 2002-2005 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version Release: @package_version@
 * @link http://pear.php.net/LiveUser_Admin
 */
class LiveUser_Admin_Auth_Storage_MDB2 extends LiveUser_Admin_Storage_MDB2
{
    /**
     * Initializes database storage container.
     * Merges tables/fields/aliases together if needed or set the default
     * ones if any of those vars are empty.
     *
     * @param array &$storageConf Storage Configuration
     * @return void
     *
     * @access public
     * @uses LiveUser_Admin_Storage_MDB2::init
     */
    function init(&$storageConf)
    {
        parent::init($storageConf);

        require_once 'LiveUser/Auth/Storage/Globals.php';
        if (empty($this->tables)) {
            $this->tables = $GLOBALS['_LiveUser']['auth']['tables'];
        } else {
            $this->tables = LiveUser::arrayMergeClobber($GLOBALS['_LiveUser']['auth']['tables'], $this->tables);
        }
        if (empty($this->fields)) {
            $this->fields = $GLOBALS['_LiveUser']['auth']['fields'];
        } else {
            $this->fields = LiveUser::arrayMergeClobber($GLOBALS['_LiveUser']['auth']['fields'], $this->fields);
        }
        if (empty($this->alias)) {
            $this->alias = $GLOBALS['_LiveUser']['auth']['alias'];
        } else {
            $this->alias = LiveUser::arrayMergeClobber($GLOBALS['_LiveUser']['auth']['alias'], $this->alias);
        }
    }
}
?>
