<?php require_once 'index.php'; ?>
<h3>UserRights</h3>
<?php
$users = $admin->searchUsers();
if  (empty($users)) {
    echo 'Run the <b>User</b> test first<br />';
    exit;
}

$rights = $admin->perm->getRights();
if  (empty($rights)) {
    echo 'Run the <b>Right</b> test first<br />';
    exit;
}

for ($i = 1; $i < 30; $i++) {
    $user = array_rand($users);
    $right = array_rand($rights);
    $data = array(
        'perm_user_id' => $users[$user]['perm_user_id'],
        'right_id' => $rights[$right]['right_id'],
        'right_level' => 1,
    );
    $granted = $admin->perm->grantUserRight($data);

    if ($granted === false) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    } else {
        echo '<b>' . $users[$user]['name'] . '</b> was granted the right <b>' . $rights[$right]['right_id'] . '</b><br />';
    }
    unset($rights[$right]);
    $rights = array_values($rights);
}

$user = array_rand($users);
$right = array_rand($rights);
$filters = array(
    'perm_user_id' => $users[$user]['auth_user_id'],
    'right_id' => $rights[$right]['right_id']
);
$revoked = $admin->perm->revokeUserRight($filters);

if ($revoked === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    echo 'The right <b>' . $rights[$right]['right_id'] . '</b> has been revoked from <b>' . $users[$user]['name'] . '</b><br />';
}

$user = array_rand($users);
$params = array(
    'fields' => array(
        'right_id'
    ),
    'filters' => array(
        'perm_user_id' => $users[$user]['perm_user_id']
    )
);
$user_rights = $admin->perm->getRights($params);
if ($user_rights === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    $right = array_rand($user_rights);
    $filters = array(
        'perm_user_id' => $users[$user]['auth_user_id'],
        'right_id' => $user_rights[$right]['right_id']
    );
    $data = array('right_level' => 3);
    $update = $admin->perm->updateUserRight($data, $filters);
    if ($update === false) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    } else {
        echo 'The right <b>' . $user_rights[$right]['right_id'] . '</b> has been updated to Level 3 for <b>' . $users[$user]['name'] . '</b><br />';
        $params = array(
            'filters' => array(
                'right_id' => $user_rights[$right]['right_id'],
                'perm_user_id' => $users[$user]['perm_user_id']
            )
        );
        $result = $admin->perm->getRights($params);

        if ($result === false) {
            echo '<strong>Error on line: '.__LINE__.'</strong><br />';
        } else {
            Var_Dump::display($result);
        }
    }
}    

$user = array_rand($users);
$params = array(
    'fields' => array(
        'right_id',
        'right_level'
    ),
    'with' => array(
        'perm_user_id' => array(
            'fields' => array(
                'name',
            ),
        ),
    ),
    'filters' => array(
        'perm_user_id' => $users[$user]['perm_user_id']
    )
);
$singleRight = $admin->perm->getRights($params);

if ($singleRight === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    echo 'These are the user rights for <b>' . $users[$user]['name'] . '</b>:<br />';
    Var_Dump::display($singleRight);
    echo '<br />';
}

$params = array(
    'fields' => array(
        'right_id',
        'right_level'
    ),
    'with' => array(
        'perm_user_id' => array(
            'fields' => array(
                'name',
            ),
        ),
    ),
);

$rights = $admin->perm->getRights($params);
if ($rights === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    echo 'Here are all the rights:<br />';
    Var_Dump::display($rights);
    echo '<br />';
}
echo '<hr />';
