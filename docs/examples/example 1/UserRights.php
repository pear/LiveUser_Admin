<?php require_once 'index.php'; ?>
<h3>UserRights</h3>
<?php
$currentUser = $admin->searchUsers();
if  (empty($currentUser)) {
    echo 'Run the <b>User</b> test first<br />';
    exit;
}

$currentRight = $admin->perm->getRights();
if  (empty($currentRight)) {
    echo 'Run the <b>Right</b> test first<br />';
    exit;
}

$users = $admin->searchUsers();
$rights = $admin->perm->getRights();

for ($i = 1; $i < 30; $i++) {
    $randUser = array_rand($users);
    $randRight = array_rand($rights);
    $data = array(
        'perm_user_id' => $users[$randUser]['perm_user_id'],
        'right_id' => $rights[$randRight]['right_id'],
        'right_level' => 1,
    );
    $granted = $admin->perm->grantUserRight($data);

    if (!$granted) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    } else {
        echo $users[$randUser]['name'].' was granted the right <b>'.$rights[$randRight]['right_id'].'</b><br />';
    }
}

$randUser = array_rand($users);
$randRight = array_rand($rights);
$filters = array(
    'perm_user_id' => $users[$randUser]['auth_user_id'],
    'right_id' => $rights[$randRight]['right_id']
);
$revoked = $admin->perm->revokeUserRight($filters);

if (!$revoked) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    echo 'The right <b>'.$rights[$randRight]['right_id'].'</b> has been revoked from <b>'.$users[$randUser]['name'].'</b><br />';
}

$randUser = array_rand($users);
$params = array(
    'fields' => array(
        'right_id'
    ),
    'filters' => array(
        'perm_user_id' => $users[$randUser]['perm_user_id']
    )
);
$user_rights = $admin->perm->getRights($params);
if (!$user_rights) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    $randRight = array_rand($user_rights);
    $filters = array(
        'perm_user_id' => $users[$randUser]['auth_user_id'],
        'right_id' => $user_rights[$randRight]['right_id']
    );
    $data = array('right_level' => 3);
    $update = $admin->perm->updateUserRight($data, $filters);
    if (!$update) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    } else {
        echo 'The right <b>'.$user_rights[$randRight]['right_id'].'</b> has been updated to Level 3 for <b>'.$users[$randUser]['name'].'</b><br />';
        $params = array(
            'filters' => array(
                'right_id' => $user_rights[$randRight]['right_id'],
                'perm_user_id' => $users[$randUser]['perm_user_id']
            )
        );
        $result = $admin->perm->getRights($params);

        if (!$result) {
            echo '<strong>Error on line: '.__LINE__.'</strong><br />';
        } else {
            Var_Dump::display($result);
        }
    }
}    

$randUser = array_rand($users);
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
        'perm_user_id' => $users[$randUser]['perm_user_id']
    )
);
$singleRight = $admin->perm->getRights($params);

if (!$singleRight) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    echo 'These are the user rights for <b>'.$users[$randUser]['name'].'</b>:<br />';
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
$allRights = $admin->perm->getRights($params);
if (!$allRights) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    echo 'Here are all the rights:<br />';
    Var_Dump::display($allRights);
    echo '<br />';
}
echo '<hr />';
