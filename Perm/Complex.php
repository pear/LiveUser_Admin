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
 * DB_Complex permission administration class
 *
 * @package  LiveUser
 * @category authentication
 */

/**
 * Require the parent class definition
 */
require_once 'LiveUser/Admin/Perm/Medium.php';

/**
 * This class provides a set of functions for implementing a user
 * permission management system on live websites. All authorisation
 * backends/containers must be extensions of this base class.
 *
 * @author  Christian Dickmann <dickmann@php.net>
 * @author  Markus Wolff <wolff@21st.de>
 * @author  Matt Scifo <mscifo@php.net>
 * @version $Id$
 * @package LiveUser
 */
class LiveUser_Admin_Perm_Complex extends LiveUser_Admin_Perm_Medium
{
    /**
     * Constructor
     *
     * @access protected
     * @param  mixed      configuration array
     * @return void
     */
    function LiveUser_Admin_Perm_Complex(&$confArray)
    {
        $this->selectable_tables['getRights'][] = 'right_implied';
        $this->selectable_tables['getAreas'][] = 'area_admin_areas';
        $this->selectable_tables['getGroups'][] = 'group_subgroups';
        $this->LiveUser_Admin_Perm_Medium($confArray);
    }

    /**
     *
     *
     * @access public
     * @param array $data
     * @return
     */
    function assignSubGroup($data)
    {
        if (isset($data['group_id']) && isset($data['subgroup_id']) &&
            $data['subgroup_id'] == $data['group_id']
        ) {
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
     * @access public
     * @param array $filters
     * @return
     */
    function unassignSubGroup($filters)
    {
        $result = $this->_storage->delete('group_subgroups', $filters);
        // notify observer
        return $result;
    }

    /**
     *
     *
     * @access public
     * @param array $data
     * @return
     */
    function implyRight($data)
    {
        if (isset($data['right_id']) && isset($data['implied_righ_id']) &&
            $data['implied_right_id'] == $data['right_id']
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
     *
     *
     * @access public
     * @param array $filters
     * @return
     */
    function unimplyRight($filters)
    {
        $result = $this->_storage->delete('right_implied', $filters);
        if ($result === false) {
            return false;
        }

        $this->_updateImpliedStatus($filters);

        // notify observer
        return $result;
    }

    /**
     *
     *
     * @access public
     * @param array $data
     * @return
     */
    function addAreaAdmin($data)
    {
        // needs more sanity checking, check if the perm_id is really perm_type 3 and so on
        // make sure when removing rights or updating them that if the user goes down 
        // below perm_type 3 that a entry from area_admins_areas is removed

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
            )
        );
        $result = parent::getUsers($params);
        if ($result === false) {
            return $result;
        }

        if ($result[0]['perm_type'] < 3) {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'The user doesn\'t have sufficient rights')
            );
            return false;
        }

        $result = $this->_storage->insert('area_admins_areas', $data);

        // notify observer
        return $result;
    }

    /**
     *
     *
     * @access public
     * @param array $filters
     * @return
     */
    function removeAreaAdmin($filters)
    {
        $filters = $this->_makeRemoveFilter($filters, 'perm_user_id', 'getAreas');
        if (!$filters) {
            return $filters;
        }

        $result = $this->_storage->delete('area_admin_areas', $filters);
        if ($result === false) {
            return $result;
        }
    }

    /**
     *
     *
     * @access public
     * @param array $filters
     * @return
     */
    function removeArea($filters)
    {
        $filters = $this->_makeRemoveFilter($filters, 'area_id', 'getAreas');
        if (!$filters) {
            return $filters;
        }

        $result = $this->removeAreaAdmin($filters);
        if ($result === false) {
            return $result;
        }

        $result = parent::removeArea($filters);

        // notify observer
        return $result;
    }

    /**
     *
     *
     * @access public
     * @param array $data
     * @param array $filters
     * @return
     */
    function updateRight($data, $filters)
    {
        if (isset($data['perm_type']) && $data['perm_type']  < 3) {
            if (!is_numeric($filters['perm_user_id'])) {
                $this->_stack->push(
                    LIVEUSER_ADMIN_ERROR_FILTER, 'exception',
                    array('key' => 'perm_user_id')
                );
                return false;
            }

            $params = array(
                'fields' => array(
                    'perm_type'
                ),
                'filters' => array(
                    'perm_user_id' => $filters['perm_user_id']
                )
            );
            $result = parent::getRight($params);
            if ($result === false) {
                return $result;
            }

            if ($result['perm_type'] > 2) {
                $this->removeAreaAdmin($params['filters']);
            }
        }
        return parent::updateRight($data, $filters);
    }

    /**
     *
     *
     * @access public
     * @param array $filters
     * @return
     */
    function removeRight($filters)
    {
        $filters = $this->_makeRemoveFilter($filters, 'right_id', 'getRights');
        if (!$filters) {
            return $filters;
        }

        $result = $this->_storage->delete('right_implied', $filters);
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
     *
     *
     * @access public
     * @param array $filters
     * @return
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
     *
     *
     * @access private
     * @param array $params
     * @return
     */
    function _getSubGroups($params = array())
    {
        $selectable_tables = array('group_subgroups');
        $root_table = 'group_subgroups';

        $data = $this->_makeGet($params, $root_table, $selectable_tables);
        return $data;
    }

    /**
     *
     *
     * @access private
     * @param array $params
     * @return
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
     * option revursive is passed on as true.
     *
     * @access public
     * @param array $filters
     * @return
     */
    function removeGroup($filters)
    {
        if (isset($filters['recursive'])) {
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

        $result = $this->_storage->delete('group_subgroups', $filters);
        if ($result === false) {
            return false;
        }

        return parent::removeGroup($filters);
    }

    /**
     *
     *
     * @access public
     * @param array $data
     * @return
     */
    function grantGroupRight($data)
    {
        $result = parent::grantGroupRight($data);
        // notify observer
        return $result;
    }

    /**
     *
     *
     * @access public
     * @param array $filters
     * @return
     */
    function _updateImpliedStatus($filters)
    {
        $count = $this->_storage->selectCount('right_implied', 'right_id', $filters);
        if ($count === false) {
            return false;
        }

        if (isset($filters['right_id'])) {
            $rightId = $filters['right_id'];
        } elseif (isset($filters['implied_right_id'])) {
            $params = array(
                'fields' => array(
                    'right_id'
                ),
                'filters' => array(
                    'implied_right_id' => $filters['implied_right_id']
                )
            );

            $result = $this->getRights($params);
            if ($result === false) {
                return false;
            }

            if (empty($result)) {
                $this->_stack->push(
                    LIVEUSER_ADMIN_ERROR, 'exception',
                    array('msg' => 'No right implies right id ' . $filters['implied_right_id'])
                );
                return false;
            }

            $rightId = $result['right_id'];
        } else {
            $this->_stack->push(
                LIVEUSER_ADMIN_ERROR, 'exception',
                array('msg' => 'Neither right_id nor implied_right_id were set
                                in the filter.')
            );
            return false;
        }

        $data = array('has_implied' => (bool)$count);
        $filter = array('right_id' => $rightId);

        $result = $this->updateRight($data, $filter);
        if ($result === false) {
            return false;
        }

        // notify observer
        return $result;
    }

    /**
     *
     *
     * @access public
     * @param int $subGroupId
     * @return
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
     *
     *
     * @access public
     * @param array $params
     * @return array
     */
    function getGroups($params = array())
    {
        !isset($params['hierarchy']) ? $params['hierarchy'] = false : null;
        !isset($params['subgroups']) ? $params['subgroups'] = true : null;

        if ($params['subgroups']) {
            $old_rekey = isset($params['rekey']) ?  $params['rekey'] : false;
            $params['rekey'] = true;
        }

        $_groups = parent::getGroups($params);
        if ($_groups === false) {
            return $_groups;
        }

        if ($params['subgroups']) {
            $param = array(
                'fields' => array(
                    'subgroup_id',
                    'group_id'
                )
            );
            $subgroups = $this->_getSubGroups($param);
            if ($subgroups === false) {
                return $subgroups;
            }

            // first it will make all subgroups, then it will update all subgroups to keep
            // everything up2date
            for ($i = 0; $i < 2; $i++) {
                foreach($subgroups as $subgroup) {
                    if (isset($_groups[$subgroup['group_id']])) {
                        $_groups[$subgroup['group_id']]['subgroups'][$subgroup['subgroup_id']] = 
                            $_groups[$subgroup['subgroup_id']];
                    }
                }
            }

            if ($params['hierarchy']) {
               foreach($subgroups as $subgroup) {
                    if ($_groups[$subgroup['subgroup_id']]) {
                       unset($_groups[$subgroup['subgroup_id']]);
                    }
                }
            }

            if ($old_rekey) {
                return $_groups;
            } else {
                $groups = array();
                foreach ($_groups as $key => $values) {
                    $groups[] = array_merge(array('group_id' => $key), $values);
                }
                return $groups;
            }
        }

        return $_groups;
    }

    /**
     *
     *
     * @access public
     * @param array $params
     * @return array
     */
    function getRights($params = array())
    {
        !isset($params['hierarchy']) ? $params['hierarchy'] = false : null;
        !isset($params['inherited']) ? $params['inherited'] = false : null;
        !isset($params['implied']) ? $params['implied'] = false : null;
        /*$old_rekey = isset($params['rekey']) ?  $params['rekey'] : false;

        $params['rekey'] = true;*/
        $rights = parent::getRights($params);
        if ($rights === false) {
            return $rights;
        }

        $_rights = array();
        if (is_array($rights)) {
            foreach ($rights as $value) {
                $id = $value['right_id'];
                $_rights[$id] = $value;

                if ($params['implied']) {
                    $param = array(
                        'filters' => array(
                            'right_id' => $id
                        )
                    );
                    $implied_rights = $this->_getImpliedRights($param);

                    if ($implied_rights === false) {
                        return $implied_rights;
                    }

                    foreach($implied_rights as $right) {
                        if ($_rights[$right['right_id']]) {
                            continue;
                        }

                        $right['type'] = 'implied';

                        if ($params['hierarchy']) {
                            $_rights[$id]['implied_rights'][$right['right_id']] = $right;
                            unset($_rights[$right['right_id']]);
                        } else {
                            $_rights[$right['right_id']] = $right;
                        }
                    }
                }

                if (!isset($_rights[$id]['type']) || !$_rights[$id]['type']) {
                    $_rights[$id]['type'] = 'granted';
                }
            }
        }

        if ($params['inherited'] &&
                (isset($params['filters']['perm_user_id']) || 
                 isset($params['filters']['group_id']))
        ) {
            $inherited_rights = $this->_getInheritedRights($params);

            if ($inherited_rights === false) {
                return $inherited_rights;
            }

            foreach ($inherited_rights as $right) {
                if ($_rights[$right['right_id']]) {
                    continue;
                }

                $right['type'] = 'inherited';
                $_rights[$right['right_id']] = $right;
            }
        }

        return $_rights;
    }

    /**
     *
     *
     * @access private
     * @param array $params
     * @return array
     */
    function _getImpliedRights($params = array())
    {
        $selectable_tables = $this->selectable_tables['getRights'];
        $root_table = 'right_implied';

        $result = $this->_makeGet($params, $root_table, $selectable_tables);

        $_rights = array();
        foreach ($result as $row) {
            $params['filters']['right_id'] = $row['right_id'];
            $implied_rights = $this->getRights($params);
            if ($implied_rights === false) {
                return $implied_rights;
            }

            $_rights = array_merge($_rights, $implied_rights);
        }

        return $_rights;
    }

    /**
     *
     *
     * @access private
     * @param array $params
     * @return array
     */
    function _getInheritedRights($params = array())
    {
        if ($params['filters']['perm_user_id']) {
            $select = array();
            $root_table = 'groupusers';
        } else {
            $select = array('group_subgroups');
            $root_table = 'group_subgroups';
        }

        $result = $this->getGroup($params, $select, $root_table);
        if ($result === false) {
            return $result;
        }

        $_rights = array();
        foreach ($result as $row) {
            $params['filters']['perm_user_id'] = null;
            $params['filters']['group_id'] = $row['group_id'];
            $inherited_rights = $this->getRights($params);
            if ($inherited_rights === false) {
                return $inherited_rights;
            }

            $_rights = array_merge($_rights, $inherited_rights);
        }

        return $_rights;
    }
}
?>
