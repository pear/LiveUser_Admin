<?php require_once 'index.php'; ?>
<h3>Group</h3>
<?php
// Add
for ($i = 1; $i < 20; $i++) {
    $data = array('group_define_name' => 'GROUP' . $i);
    $groupAdd = $admin->perm->addGroup($data);

    if (!$groupAdd) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    } else {
        echo 'Added group <b>Group'.$i.'</b><br />';
    }
}

// Get
echo 'All the groups:<br />';
$allGroups = $admin->perm->getGroups();

if (!$allGroups) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    Var_Dump::display($allGroups);
    echo '<br />';
}

// Remove
$id = array_rand($allGroups);
$filters = array('group_id' => $allGroups[$id]['group_id']);
$removed = $admin->perm->removeGroup($filters);

if (!$removed) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    echo $allGroups[$id]['group_id'] . ' was deleted<br />';
}

// Update
$id = array_rand($allGroups);
$filters = array('group_id' => $allGroups[$id]['group_id']);
$data = array('group_define_name' => 'GROUP_' . $allGroups[$id]['group_id'] . '_UPDATED');
$updated = $admin->perm->updateGroup($data, $filters);
echo $admin->perm->_storage->dbc->last_query;
if (!$updated) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    echo $allGroups[$id]['group_id'] . ' was updated<br />';
    $params = array('filters' => array('group_id' => $allGroups[$id]['group_id']));
    $group = $admin->perm->getGroups($params);

    if (!$group) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    } else {
        Var_Dump::display($group);
        echo '<br />';
    }
}

// Get
echo 'All the groups:<br />';

$allGroups = $admin->perm->getGroups();
if (!$allGroups) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    Var_Dump::display($allGroups);
    echo '<br />';
}
echo '<hr />';
