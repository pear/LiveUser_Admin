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
        echo '<strong>Error on line: '.__LINE__.' last query: '.$admin->perm->_storage->dbc->last_query.'</strong><br />';
    } else {
        echo 'Created User Id <b>' . $user_id . '</b><br />';
    }
}

// Get
// Group of users
echo 'All the users:<br />';
$allUsers = $admin->searchUsers();

if ($allUsers === false) {
    echo '<strong>Error on line: '.__LINE__.' last query: '.$admin->perm->_storage->dbc->last_query.'</strong><br />';
} else {
    Var_Dump::display($allUsers);
    echo '<br />';
}

$id = array_rand($allUsers);
// single user
echo 'This user will be removed:<br />';
$user = $admin->getUser($allUsers[$id]['perm_user_id']);
if ($user === false) {
    echo '<strong>Error on line: '.__LINE__.' last query: '.$admin->perm->_storage->dbc->last_query.'</strong><br />';
} else {
    Var_Dump::display($user);
    echo '<br />';
}

// Remove
$removed = $admin->removeUser($allUsers[$id]['perm_user_id']);

if ($removed === false) {
    echo '<strong>Error on line: '.__LINE__.' last query: '.$admin->perm->_storage->dbc->last_query.'</strong><br />';
} else {
    echo '<b>' . $id . '</b> was deleted<br />';
    unset($allUsers[$id]);
}

// Update
$id = array_rand($allUsers);
$updateUser = $allUsers[$id]['perm_user_id'];
$updated = $admin->updateUser($updateUser, 'updated_user'.rand(), 'foo', array(), $custom);
if ($updated === false) {
    echo '<strong>Error on line: '.__LINE__.' last query: '.$admin->perm->_storage->dbc->last_query.'</strong><br />';
} else {
    echo '<b>' . $updateUser . '</b> was updated<br />';
    $user = $admin->getUser($updateUser);

    if ($user === false) {
        echo '<strong>Error on line: '.__LINE__.' last query: '.$admin->perm->_storage->dbc->last_query.'</strong><br />';
    } else {
        Var_Dump::display($user);
        echo '<br />';
    }
}

// Get
echo 'All the users:<br />';
$allUsers = $admin->searchUsers();
if ($allUsers === false) {
    echo '<strong>Error on line: '.__LINE__.' last query: '.$admin->perm->_storage->dbc->last_query.'</strong><br />';
} else {
    Var_Dump::display($allUsers);
    echo '<br />';
}

$randUser = array_rand($allUsers);

unset($user);
echo 'Test fetching auth_user_id AND perm_user_id with PERM getUsers()<br />';
echo 'Auth<br />';
$filter = array(array('cond' => '', 'name' => 'auth_user_id', 'op' => '=', 'value' => $allUsers[$randUser]['auth_user_id'], 'type' => 'text'));
$options = array('with_rights' => true);
$user = $admin->auth->getUsers($filter);
if ($user === false) {
    echo '<strong>Error on line: '.__LINE__.' last query: '.$admin->perm->_storage->dbc->last_query.'</strong><br />';
} else {
    Var_Dump::display($user);
    echo '<br />';
}
unset($user);

echo 'Perm<br />';
$filter = array(array('filters' => array('perm_user_id' => '3')));
$user = $admin->perm->getUsers($filter);
if ($user === false) {
    echo '<strong>Error on line: '.__LINE__.' last query: '.$admin->perm->_storage->dbc->last_query.'</strong><br />';
} else {
    Var_Dump::display($user);
    echo '<br />';
}

echo '<hr />';
