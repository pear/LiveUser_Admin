<?php require_once 'index.php'; ?>
<h3>GroupRights</h3>
<?php
$groups = $admin->perm->getGroups();
if  (empty($groups)) {
    echo 'Run the <b>Group</b> test first<br />';
    exit;
}

$rights = $admin->perm->getRights();
if  (empty($rights)) {
    echo 'Run the <b>Right</b> test first<br />';
    exit;
}


for ($i = 0; $i < 20; $i++) {
    $right   = array_rand($rights);
    $group = array_rand($groups);
    $data = array(
        'group_id' => $groups[$group]['group_id'],
        'right_id' => $rights[$right]['right_id']
    );
    $granted = $admin->perm->grantGroupRight($data);

    if ($granted === false) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    } else {
        echo 'Group <b>' . $groups[$group]['group_id'] . '</b> was granted the right <b>'.$rights[$right]['right_id'].'</b><br />';
    }
    unset($rights[$right]);
    $rights = array_values($rights);
}

$group = array_rand($groups);
$params = array(
    'fields' => array(
        'right_id',
        'right_define_name',
    ),
    'with' => array(
        'group_id' => array(
            'fields' => array(
                'group_id',
            ),
        ),
    ),
    'filters' => array(
        'group_id' => $groups[$group]['group_id']
    ),
    'limit' => 10,
    'offset' => 0,
);
$allGroupRights = $admin->perm->getRights($params);

if ($allGroupRights === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    echo '<hr />Here is/are <b>' . count($allGroupRights) . '</b> group right(s) for the group <b>' . $groups[$group]['group_id'] . '</b>:<br />';
    Var_Dump::display($allGroupRights);
    echo '<br />';
}

$right   = array_rand($rights);
$group = array_rand($groups);
$filters = array(
    'right_id' => $rights[$right]['right_id'],
    'group_id' => $groups[$group]['group_id']
);
$removed = $admin->perm->revokeGroupRight($filters);

if ($removed === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    print_r($admin->getErrors());
} else {
    echo 'Removed the right <b>'.$right.'</b> on group <b>'.$group.'</b><br />';
}


$group = array_rand($groups);
$params = array(
    'fields' => array(
        'right_id'
    ),
    'filters' => array(
        'group_id' => $groups[$group]['group_id']
    )
);
$rights_group = $admin->perm->getRights($params);
if ($rights_group === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    print_r($admin->getErrors());
} else {
    $right   = array_rand($rights_group);
    $data = array('right_level' => 2);
    $filters = array(
        'right_id' => $rights_group[$right]['right_id'],
        'group_id' => $groups[$group]['group_id']
    );
    $updated = $admin->perm->updateGroupRight($data, $filters);

    if ($updated === false) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
        print_r($admin->getErrors());
    } else {
        echo 'Updated the right level of <b>' . $groups[$group]['group_id'] . '</b><br />';
        $params = array(
            'fields' => array(
                'right_id'
            ),
            'filters' => array(
                'right_id' => $rights_group[$right]['right_id'],
                'group_id' => $groups[$group]['group_id']
            )
        );
        $result = $admin->perm->getRights($params);

        if ($result === false) {
            echo '<strong>Error on line: '.__LINE__.'</strong><br />';
            print_r($admin->getErrors());
        } else {
            Var_Dump::display($result);
        }
    }
}

$params = array(
    'fields' => array(
        'right_id',
    ),
    'with' => array(
        'group_id' => array(
            'fields' => array(
                'group_id',
                'right_level',
            )
        ),
    ),
);

$allGroups = $admin->perm->getRights($params);
echo 'Here are all the group rights after the changes:<br />';
if ($allGroups === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    print_r($admin->getErrors());
} else {
    Var_Dump::display($allGroups);
}
echo '<hr />';
