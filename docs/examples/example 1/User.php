<?php require_once 'index.php'; ?>
<h3>User</h3>
<h4>for this test to work you need to add a "name" and an "email" field to your auth user table</h4>
<?php
// Add
for ($i = 1; $i < 10; $i++) {
    $custom = array(
        'name'  => 'asdf'.$i,
        'email' => 'fleh@example.com'.$i
    );

    $user_id = $admin->addUser('johndoe' . rand(), 'dummypass', array(), $custom, null, '1');
    if ($user_id === false) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
        print_r($admin->getErrors());
    } else {
        echo 'Created User Id <b>' . $user_id . '</b><br />';
    }
}

// Get
// Group of users
echo 'All the users:<br />';
$users = $admin->searchUsers();

if ($users === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    print_r($admin->getErrors());
} else {
    Var_Dump::display($users);
    echo '<br />';
}

$id = array_rand($users);
// single user
echo 'This user will be removed:<br />';
$user = $admin->getUser($users[$id]['perm_user_id']);
if ($user === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    print_r($admin->getErrors());
} else {
    Var_Dump::display($user);
    echo '<br />';
}

// Remove
$removed = $admin->removeUser($users[$id]['perm_user_id']);

if ($removed === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    print_r($admin->getErrors());
} else {
    echo '<b>' . $id . '</b> was deleted<br />';
    unset($users[$id]);
}

// Update
$id = array_rand($users);
$updateUser = $users[$id]['perm_user_id'];
$updated = $admin->updateUser($updateUser, 'updated_user'.rand(), 'foo', array(), $custom);
if ($updated === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    print_r($admin->getErrors());
} else {
    echo '<b>' . $updateUser . '</b> was updated<br />';
    $user = $admin->getUser($updateUser);

    if ($user === false) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
        print_r($admin->getErrors());
    } else {
        Var_Dump::display($user);
        echo '<br />';
    }
}

// Get
echo 'All the users:<br />';

$users = $admin->searchUsers();
if ($users === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    print_r($admin->getErrors());
} else {
    Var_Dump::display($users);
    echo '<br />';
}

$user = array_rand($users);

echo 'Test fetching auth_user_id AND perm_user_id with PERM getUsers()<br />';
echo 'Auth<br />';
$filter = array(array('cond' => '', 'name' => 'auth_user_id', 'op' => '=', 'value' => $users[$user]['auth_user_id'], 'type' => 'text'));
$options = array('with_rights' => true);
$user = $admin->auth->getUsers($filter);
if ($user === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    print_r($admin->getErrors());
} else {
    Var_Dump::display($user);
    echo '<br />';
}
unset($user);

echo 'Perm<br />';
$filter = array(array('filters' => array('perm_user_id' => '3')));
$user = $admin->perm->getUsers($filter);
if ($user === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    print_r($admin->getErrors());
} else {
    Var_Dump::display($user);
    echo '<br />';
}

echo '<hr />';
