<?php require_once 'index.php'; ?>
<h3>Group</h3>
<?php
// Add
for ($i = 1; $i < 20; $i++) {
    $data = array('group_define_name' => 'GROUP'.rand());
    $groupId = $admin->perm->addGroup($data);

    if ($groupId === false) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    } else {
        echo 'Created Group Id <b>'.$groupId.'</b><br />';
    }
}

// Get
echo 'All the groups:<br />';
$allGroups = $admin->perm->getGroups();

if ($allGroups === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    Var_Dump::display($allGroups);
    echo '<br />';
}

// Remove
$id = array_rand($allGroups);
$filters = array('group_id' => $allGroups[$id]['group_id']);
$removed = $admin->perm->removeGroup($filters);

if ($removed === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    echo '<b>' . $allGroups[$id]['group_id'] . '</b> was deleted<br />';
    unset($allGroups[$id]);
}

// Update
$id = array_rand($allGroups);
$filters = array('group_id' => $allGroups[$id]['group_id']);
$data = array('group_define_name' => 'GROUP_' . $allGroups[$id]['group_id'] . '_UPDATED');
$updated = $admin->perm->updateGroup($data, $filters);

if ($updated === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    echo '<b>' . $allGroups[$id]['group_id'] . '</b> was updated<br />';
    $params = array('filters' => array('group_id' => $allGroups[$id]['group_id']));
    $group = $admin->perm->getGroups($params);

    if ($group === false) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    } else {
        Var_Dump::display($group);
        echo '<br />';
    }
}

// Get
echo 'All the groups:<br />';

$allGroups = $admin->perm->getGroups();
if ($allGroups === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    Var_Dump::display($allGroups);
    echo '<br />';
}
echo '<hr />';
