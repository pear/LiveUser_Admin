<?php require_once 'index.php'; ?>
<h3>Subgroups</h3>
<?php
$groups = $admin->perm->getGroups();
if  (empty($groups)) {
    echo 'Run the <strong>Group</strong> test first<br />';
    exit;
}

for ($i = 0; $i < 10; $i++) {
    $group = array_rand($groups);
    $subgroup = array_rand($groups);
 
    $data = array(
        'group_id' => $groups[$group]['group_id'],
        'subgroup_id' => $groups[$subgroup]['group_id']
    );
    $assign = $admin->perm->assignSubGroup($data);

    if ($assign === false) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
        print_r($admin->getErrors());
    } else {
        echo '<strong>' . $groups[$subgroup]['group_id'] . '</strong> is now 
              subgroup of <strong>'. $groups[$group]['group_id'] .'</strong><br />';
    }
}

echo 'All the groups:<br />';
$groups = $admin->perm->getGroups();
if ($groups === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    print_r($admin->getErrors());
} else {
    Var_Dump::display($groups);
    echo '<br />';
}

// unassignSugroup
// By group id
$id = array_rand($groups);
$filters = array('group_id' => $groups[$id]['group_id']);
$unassign = $admin->perm->unassignSubGroup($filters);

if ($unassign === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    print_r($admin->getErrors());
} else {
    echo 'Removed all records with the group id <strong>' . $groups[$id]['group_id'] . '</strong><br />';
    unset($groups[$id]);
}

// By subgroup id
$id = array_rand($groups);
$filters = array('subgroup_id' => $groups[$id]['group_id']);
$unassign = $admin->perm->unassignSubGroup($filters);

if ($unassign === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    print_r($admin->getErrors());
} else {
    echo 'Removed all records with the subgroup id <strong>' . $groups[$id]['group_id'] . '</strong><br />';
    unset($groups[$id]);
}
// By subgroup id and group id
$group = array_rand($groups);
$subgroup = array_rand($groups);
$filters = array(
    'group_id' => $groups[$group]['group_id'],
    'subgroup_id' => $groups[$subgroup]['group_id']
);
$unassign = $admin->perm->unassignSubGroup($filters);

if ($unassign === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    print_r($admin->getErrors());
} else {
    echo 'Removed the record that has <strong>' . $groups[$group]['group_id'] . '</strong>
          as group id  and <strong>' . $groups[$subgroup]['group_id'] . '</strong> as subgroup id<br />';
}

echo '<br /><br />Test getParentGroup:<br />';
for ($i = 0; $i < 5; $i++) {
    $subgroup = array_rand($groups);
    $result = $admin->perm->getParentGroup($groups[$subgroup]['group_id']);
    if ($result === false) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
        print_r($admin->getErrors());
    } else {
        echo 'Group <strong>' . $result['group_id'] . '</strong> is the parent group of <strong>' . $groups[$subgroup]['group_id'] . '</strong><br />';
    }
}

// Get
echo '<br /><br />All the groups:<br />';
$groups = $admin->perm->getGroups();
if ($groups === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    print_r($admin->getErrors());
} else {
    Var_Dump::display($groups);
    echo '<br />';
}

echo '<br /><br />All the groups with hierarchy mode on and rekey to true:<br />';
$params = array(
    'hierarchy' => true,
    'rekey' => true
);
$groups = $admin->perm->getGroups($params);
if ($groups === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    print_r($admin->getErrors());
} else {
    Var_Dump::display($groups);
    echo '<br />';
}
echo '<hr />';