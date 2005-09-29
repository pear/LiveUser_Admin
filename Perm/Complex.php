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
 * @author Markus Wolff <wolff@21st.de>
 * @author Helgi �ormar �orbj�rnsson <dufuz@php.net>
 * @author Lukas Smith <smith@pooteeweet.org>
 * @author Arnaud Limbourg <arnaud@php.net>
 * @author Christian Dickmann <dickmann@php.net>
 * @author Matt Scifo <mscifo@php.net>
 * @author Bjoern Kraus <krausbn@php.net>
 * @copyright 2002-2005 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version CVS: $Id$
 * @link http://pear.php.net/LiveUser_Admin
 */

/**
 * Require the parent class definition
 */
require_once 'LiveUser/Admin/Perm/Medium.php';

/**
 * Complex permission administration class
 *
 * This class provides a set of functions for implementing a user
 * permission management system on live websites. All authorisation
 * backends/containers must be extensions of this base class.
 *
 * @category authentication
 * @package  LiveUser_Admin
 * @author  Christian Dickmann <dickmann@php.net>
 * @author  Markus Wolff <wolff@21st.de>
 * @author  Matt Scifo <mscifo@php.net>
 * @author Helgi �rmar �rbj�nsson <dufuz@php.net>
 * @copyright 2002-2005 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version Release: @package_version@
 * @link http://pear.php.net/LiveUser_Admin
 */
class LiveUser_Admin_Perm_Complex extends LiveUser_Admin_Perm_Medium
{
    /**
     * Constructor
     *
     *
     * @param  mixed      configuration array
     * @return void
     *
     * @access protected
     */
    function LiveUser_Admin_Perm_Complex()
    {
        $this->LiveUser_Admin_Perm_Medium();
        $this->selectable_tables['getRights'][] = 'right_implied';
        $this->selectable_tables['getAreas'][] = 'area_admin_areas';
        $this->selectable_tables['getGroups'][] = 'group_subgroups';
    }

    /**
     * Assign subgroup to parent group.
     *
     * First checks if groupId and subgroupId are the same then if
     * the child group is already assigned to the parent group and last if
     * the child group does have a parent group already assigned to it.
     * (Just to difference between what kinda error was hit)
     *
     * If so it returns false and pushes the error into stack
     *
     * @param array $data
     * @return mixed false on error, blah on success
     *
     * @access public
     */
    function assignSubGroup($data)
    {
        if ($data['subgroup_id'] == $data['group_id']) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Parent group id is the same as the subgroup id')
            );
            return false;
        }

        $filter = array('subgroup_id' => $data['subgroup_id']);
        $result = $this->_storage->selectCount('group_subgroups', 'group_id', $filter);
        if ($result === false) {
            return false;
        }

        if ($result == $data['group_id']) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'This child group is already a Parent of this group')
            );
            return false;
        }

        if (!empty($result)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Child group already has a parent group')
            );
            return false;
        }

        $result = $this->_storage->insert('group_subgroups', $data);
        // notify observer
        return $result;
    }

    /**
     * Don't let the function name fool ya, it actually can remove more
     * then one subgroup record at a time via the filters.
     * Most of the time you pass either group_id or subgroup_id or both
     * with the filters to remove one or more record.
     *
     *
     * @param array $filters
     * @return
     *
     * @access public
     */
    function unassignSubGroup($filters)
    {
        $result = $this->_storage->delete('group_subgroups', $filters);
        // notify observer
        return $result;
    }

    /**
     * Imply Right
     *
     *
     * @param array $data
     * @return
     *
     * @access public
     */
    function implyRight($data)
    {
        if (array_key_exists('right_id', $data) && array_key_exists('implied_right_id', $data)
            && $data['implied_right_id'] == $data['right_id']
        ) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Right id is the same as the implied right id')
            );
            return false;
        }

        $params = array(
            'fields' => array(
                'right_id'
            ),
            'filters' => array(
                'implied_right_id' => $data['implied_right_id'],
                'right_id' => $data['right_id']
            )
        );

        $result = $this->_getImpliedRight($params);
        if ($result === false) {
            return false;
        }

        if (!empty($result)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'This implied right is already implied from this right')
            );
            return false;
        }

        $result = $this->_storage->insert('right_implied', $data);
        if ($result === false) {
            return false;
        }

        $filter = array('right_id' => $data['right_id']);
        $this->_updateImpliedStatus($filter);

        // notify observer
        return $result;
    }

    /**
     * Unimply Right
     *
     *
     * @param array $filters
     * @return
     *
     * @access public
     */
    function unimplyRight($filters, $update = true)
    {
        $filters = $this->_makeRemoveFilter($filters, 'right_id', 'getRights');
        if (!$filters) {
            return $filters;
        }

        $result = $this->_storage->delete('right_implied', $filters);
        if ($result === false) {
            return false;
        }

        if ($update) {
            $this->_updateImpliedStatus($filters);
        }

        // notify observer
        return $result;
    }

    /**
     * Add Area Admin
     *
     *
     * @param array $data
     * @return
     *
     * @access public
     */
    function addAreaAdmin($data)
    {
        // needs more sanity checking, check if the perm_id is really perm_type 3 and so on
        // make sure when removing rights or updating them that if the user goes down
        // below perm_type 3 that a entry from area_admin_areas is removed

        if (!is_numeric($data['area_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => 'area_id')
            );
            return false;
        }

        if (!is_numeric($data['perm_user_id'])) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR_DATA, 'exception',
                array('key' => 'perm_user_id')
            );
            return false;
        }

        $params = array(
            'fields' => array(
                'perm_type'
            ),
            'filters' => array(
                'perm_user_id' => $data['perm_user_id']
            ),
            'select' => 'row',
        );

        $result = parent::getUsers($params);
        if ($result === false) {
            return false;
        }

        if (!array_key_exists('perm_type', $result) || $result['perm_type'] < 3) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'The user doesn\'t have sufficient rights')
            );
            return false;
        }

        $result = $this->_storage->insert('area_admin_areas', $data);

        // notify observer
        return $result;
    }

    /**
     * Remove Area Admin
     *
     *
     * @param array $filters Array containing the filters on what area admin(s)
     *                       should be removed
     * @return mixed
     *
     * @access public
     */
    function removeAreaAdmin($filters)
    {
        $result = $this->_storage->delete('area_admin_areas', $filters);
        if ($result === false) {
            return false;
        }

        // notify observer
        return $result;
    }

    /**
     * Remove Area
     *
     *
     * @param array $filters Array containing the filters on what area(s)
     *                       should be removed
     * @return
     *
     * @access public
     */
    function removeArea($filters)
    {
        $filters = $this->_makeRemoveFilter($filters, 'area_id', 'getAreas');
        if (!$filters) {
            return $filters;
        }

        $result = $this->removeAreaAdmin($filters);
        if ($result === false) {
            return false;
        }

        $result = parent::removeArea($filters);

        // notify observer
        return $result;
    }

    /**
     * Remove Right
     *
     *
     * @param array $filters Array containing the filters on what right(s)
     *                       should be removed
     * @return
     *
     * @access public
     * @uses LiveUser_Admin_Perm_Medium::removeRight
     *       LiveUser_Admin_Perm_Complex::_updateImpliedStatus
     *       LiveUser_Admin_Perm_Complex::unimplyRight
     */
    function removeRight($filters)
    {
        $result = $this->unimplyRight($filters, false);
        if ($result === false) {
            return false;
        }

        $result = parent::removeRight($filters);
        if ($result === false) {
            return false;
        }

        $this->_updateImpliedStatus($filters);

        // notify observer
        return $result;
    }

    /**
     * Remove User
     *
     *
     * @param array $filters Array containing the filters on what user(s)
     *                       should be removed
     * @return
     *
     * @access public
     * @uses LiveUser_Admin_Perm_Medium::removeUser
     *       LiveUser_Admin_Perm_Complex::updateGroup
     */
    function removeUser($filters)
    {
        $data = array('owner_user_id' => null);
        $filter = array('owner_user_id' => $filters['perm_user_id']);
        $result = $this->updateGroup($data, $filter);
        if ($result === false) {
            return false;
        }

        // notify observer
        return parent::removeUser($filters);
    }

    /**
     * Get SubGroups
     *
     *
     * @param array $params
     * @return
     *
     * @access private
     */
    function _getSubGroups($params = array())
    {
        $selectable_tables = array('group_subgroups');
        $root_table = 'group_subgroups';

        $data = $this->_makeGet($params, $root_table, $selectable_tables);
        return $data;
    }

    /**
     * Get Implied Rights
     *
     *
     * @param array $params
     * @return
     *
     * @access private
     */
    function _getImpliedRight($params = array())
    {
        $selectable_tables = array('right_implied');
        $root_table = 'right_implied';

        $data = $this->_makeGet($params, $root_table, $selectable_tables);
        return $data;
    }

    /**
     * Removes groups, can remove subgroups recursively if
     * option recursive is passed on as true.
     *
     *
     * @param array $filters Array containing the filters on what group(s)
     *                       should be removed
     * @return
     *
     * @access public
     * @uses LiveUser_Admin_Perm_Medium::removeGroup
     */
    function removeGroup($filters)
    {
        if (array_key_exists('recursive', $filters)) {
            $param = array(
                'fields' => array(
                    'subgroup_id'
                ),
                'filters' => array(
                    'group_id' => $filters['group_id']
                )
            );
            $result = $this->_getSubGroups($param);
            if ($result === false) {
                return false;
            }

            foreach ($result as $subGroupId) {
                $filter = array('group_id' => $subGroupId['subgroup_id'], 'recursive' => true);
                $result = $this->removeGroup($filter);
                if ($result === false) {
                    return false;
                }
            }
            unset($filters['recursive']);
        }

        $result = $this->unassignSubGroup($filters);
        if ($result === false) {
            return false;
        }

        return parent::removeGroup($filters);
    }

    /**
     * Grant Group Rights
     *
     *
     * @param array $data
     * @return
     *
     * @access public
     * @uses LiveUser_Admin_Perm_Medium::grantGroupRight
     */
    function grantGroupRight($data)
    {
        $result = parent::grantGroupRight($data);
        // notify observer
        return $result;
    }

    /**
     * Updates implied status
     *
     *
     * @param array $filters
     * @return
     *
     * @access private
     */
    function _updateImpliedStatus($filters)
    {
        $params = array(
            'fields' => array('right_id'),
            'filters' => $filters,
            'select' => 'col',
        );

        $rights = $this->getRights($params);
        if ($rights === false) {
            return false;
        }

        $filters = array('right_id' => $rights);

        $count = $this->_storage->selectCount('right_implied', 'right_id', $filters);
        if ($count === false) {
            return false;
        }

        $data = array('has_implied' => (bool)$count);

        $result = $this->updateRight($data, $filters);
        if ($result === false) {
            return false;
        }

        // notify observer
        return $result;
    }

    /**
     * Get parent of the group that's passed via param
     *
     *
     * @param int $subGroupId Id of the group that is used to fetch parents
     * @return array
     *
     * @access public
     */
    function getParentGroup($subGroupId)
    {
        if (!is_numeric($subGroupId)) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Something wrong with your param, make sure its a
                               numeric value and not empty')
            );
            return false;
        }

        $params = array(
            'fields' => array(
                'group_id'
            ),
            'filters' => array(
                'subgroup_id' => $subGroupId
            ),
            'select' => 'one'
        );
        $result = $this->_getSubGroups($params);

        return $result;
    }

    /**
     * Get groups
     *
     * Params:
     * subgroups - defaults to false
     *    If subgroups should be included, if false then it acts same as the
     *    medium container getGroups, if set to true it will return all subgroups
     *    like they are directly assigned, if set to 'hierarchy' it will place
     *    a tree of the subgroups under the array key 'subgroups'
     *
     *    note that 'hierarchy' requires 'rekey' enabled, 'group' is disabled,
     *    'select' set to 'all' and the first field needs to be 'group_id'
     *
     * rekey = defaults to false
     *    By default (false) we return things in this fashion
     *    <code>
     *       array(0 => array('group_id' => '1'))
     *    </code>
     *    But if rekey is turned on you get
     *    <code>
     *       array(1 => array('group_define_name' => 'FOO'))
     *    </code>
     *    Where 1 is the group_id
     *
     * @param array $params
     * @return boolean | array
     *
     * @access public
     */
    function getGroups($params = array())
    {
        $subgroup = false;
        if (array_key_exists('subgroups', $params)) {
            $subgroup = $params['subgroups'];
            unset($params['subgroups']);
        }

        if (!$subgroup
            || (array_key_exists('select', $params)
                && ($params['select'] == 'one' || $params['select'] == 'row')
            )
        ) {
            return parent::getGroups($params);
        }

        if ($subgroup === 'hierarchy') {
            return $this->_getGroupsWithHierarchy($params);
        }

        return $this->_getGroupsWithSubgroups($params);
    }

    /**
     * Helper method to fetch all groups including the subgroups
     *
     * @param array $params
     * @return boolean | array
     *
     * @access private
     */
    function _getGroupsWithSubgroups($params)
    {
        $tmp_params = array(
            'fields' => array('group_id'),
            'select' => 'col',
        );

        if (array_key_exists('filters', $params)) {
            $tmp_params['filters'] = $params['filters'];
            unset($params['filters']);
        }

        $groups = parent::getGroups($tmp_params);
        if (!$groups) {
            return $groups;
        }

        $subgroups = $groups;
        $new_count = count($subgroups);

        do {
            $tmp_params = array(
                'fields' => array(
                    'subgroup_id',
                ),
                'filters' => array(
                    'group_id' => $subgroups,
                    'subgroup_id' => array(
                        'op' => 'NOT IN',
                        'value' => $groups,
                    ),
                 ),
                'select' => 'col',
            );

            $subgroups = $this->_getSubGroups($tmp_params);
            if ($subgroups === false) {
                return false;
            }

            $groups = array_merge($groups, (array)$subgroups);
        } while(!empty($subgroups));

        $params['filters'] = array('group_id' => $groups);

        return parent::getGroups($params);
    }

    /**
     * Helper method to fetch all groups including the subgroups in a
     * hierarchy tree structure
     *
     * @param array $params
     * @return boolean | array
     *
     * @access private
     */

    function _getGroupsWithHierarchy($params)
    {
        if ((!array_key_exists('rekey', $params) || !$params['rekey'])
            || (array_key_exists('group', $params) && $params['group'])
            || (array_key_exists('select', $params) && $params['select'] != 'all')
            || (array_key_exists('fields', $params) && reset($params['fields']) !== 'group_id')
        ) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => "Setting 'subgroups' to 'hierarchy' is only allowed if 'rekey' is enabled, ".
                    "'group' is disabled, 'select' is 'all' and the first field is 'group_id'")
            );
            return false;
        }

        $groups = parent::getGroups($params);
        if (!$groups) {
            return $groups;
        }

        $group_ids = array_keys($groups);
        $tmp_params = array(
            'fields' => array(
                'group_id',
                'subgroup_id',
            ),
            'filters' => array(
                'group_id' => $group_ids,
            ),
            'rekey' => true,
            'group' => true,
        );

        $subgroups = $this->_getSubGroups($tmp_params);
        if ($subgroups === false) {
            return false;
        }

        foreach ($subgroups as $group_id => $subgroup_ids) {
            $tmp_params = $params;
            $tmp_params['subgroups'] = 'hierarchy';
            $tmp_params['filters'] = array('group_id' => $subgroup_ids);
            $subgroup_data = $this->_getGroupsWithHierarchy($tmp_params);
            if ($subgroup_data === false) {
                return false;
            }
            $groups[$group_id]['subgroups'] = $subgroup_data;
        }

        return $groups;
    }

    /**
     * Fetches rights
     *
     *
     * @param array $params
     * @return boolean | array
     *
     * @access public
     */
    function getRights($params = array())
    {
        // ensure optional parameters are set
        !array_key_exists('inherited', $params) ? $params['inherited'] = false : null;
        !array_key_exists('implied', $params) ? $params['implied'] = false : null;

        if ($params['inherited'] || $params['implied']) {
            if ((!array_key_exists('rekey', $params) || !$params['rekey'])
                || (array_key_exists('group', $params) && $params['group'])
                || (array_key_exists('select', $params) && $params['select'] != 'all')
                || (array_key_exists('fields', $params) && reset($params['fields']) !== 'right_id')
            ) {
                $this->_stack->push(
                    LIVEUSER_ADMIN_ERROR, 'exception',
                    array('msg' => "Setting 'implied' or 'inherited' is only allowed if 'rekey' is enabled, ".
                        "'group' is disabled, 'select' is 'all' and the first field is 'right_id'")
                );
                return false;
            }

            if ($params['implied']
                && array_key_exists('fields', $params)
                && !in_array('has_implied', $params['fields'])
            ) {
                $this->_stack->push(
                    LIVEUSER_ADMIN_ERROR, 'exception',
                    array('msg' => "Setting 'implied' requires that 'has_implied' field needs to be in the select list")
                );
                return false;
            }
        }

        // handle select, fields and rekey
        $rights = parent::getRights($params);
        if ($rights === false) {
            return false;
        }

        // if the result was empty or no additional work is needed
        if (empty($rights) || (!$params['inherited'] && !$params['implied'])) {
            return $rights;
        }

        // read rights inherited by (sub)groups
        if ($params['inherited']) {
            // consider adding a NOT IN filter
            $inherited_rights = $this->_getInheritedRights($params);
            if ($inherited_rights === false) {
                return false;
            }

            if (!empty($inherited_rights)) {
                foreach ($inherited_rights as $right_id => $right) {
                    if (isset($rights[$right_id])) {
                        continue;
                    }

                    $right['_type'] = 'inherited';
                    $rights[$right_id] = $right;
                }
            }
        }

        if ($params['implied']) {
            $_rights = $rights;
            $rights = array();

            foreach ($_rights as $right_id => $right) {
                if (!array_key_exists('_type', $right)) {
                    $right['_type'] = 'granted';
                }
                $rights[$right_id] = $right;
                if (!$right['has_implied']) {
                    continue;
                }

                // consider adding a NOT IN filter
                $implied_rights = $this->_getImpliedRights($right_id, $params);
                if ($implied_rights === false) {
                    return false;
                } elseif (empty($implied_rights)) {
                    continue;
                }

                foreach ($implied_rights as $implied_right_id => $right) {
                    if (isset($rights[$implied_right_id])) {
                        continue;
                    }

                    $right['_type'] = 'implied';

                    if ($params['implied'] === 'hierarchy') {
                        $rights[$right_id]['implied_rights'][$implied_right_id] = $right;
                    } else {
                        $rights[$implied_right_id] = $right;
                    }
                }
            }
        } else {
            foreach ($rights as $right_id => $right) {
                if (!isset($rights[$right_id]['_type']) || !$rights[$right_id]['_type']) {
                    $rights[$right_id]['_type'] = 'granted';
                }
            }
        }

        return $rights;
    }

    /**
     * Fetches implied rights
     *
     *
     * @param array $params
     * @return boolean | array false for error and array with impliedRights on success
     *
     * @access private
     */
    function _getImpliedRights($right_id, $params)
    {
        $selectable_tables = array('right_implied', 'rights');
        $root_table = 'right_implied';

        $param = array(
            'fields' => array('implied_right_id'),
            'select' => 'col',
            'filters' => array('right_id' => $right_id),
        );

        $result = $this->_makeGet($param, $root_table, $selectable_tables);
        if ($result === false) {
            return false;
        }

        $params['filters'] = array('right_id' => $result);
        unset($params['inherited']);
        return $this->getRights($params);
    }

    /**
     * Fetches inherited rights
     *
     *
     * @param array $params
     * @return boolean | array false for error and array with inheritedRights on success
     *
     * @access private
     */
    function _getInheritedRights($params)
    {
        $param = array(
            'fields' => array('group_id'),
            'select' => 'col',
            'filters' => $params['filters'],
            'subgroups' => true,
        );

        $result = $this->getGroups($param);
        if ($result === false) {
            return false;
        } elseif (empty($result)) {
            return array();
        }

        $params['filters'] = array('group_id' => $result);
        unset($params['implied']);
        unset($params['inherited']);
        return $this->getRights($params);
    }
}
?>
