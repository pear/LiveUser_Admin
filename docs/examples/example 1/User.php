<?php require_once 'index.php'; ?>
<h3>User</h3>
<?php
// Add
for ($i = 1; $i < 10; $i++) {
	$custom = array(
    	'name'  => 'asdf'.$i,
    	'email' => 'fleh@example.com'.$i
	);

	$user_id = $admin->addUser('johndoe' . $i, 'dummypass', array(), $custom, null, '1');
	if (!$user_id) {
		echo '<strong>Error</strong><br />';
	} else {
		echo 'Created User Id ' . $user_id . '<br />';
	}
}

// Get
// Group of users
echo 'All the users:<br />';
$allUsers = $admin->searchUsers();

if (!$allUsers) {
	echo '<strong>Error</strong><br />';
} else {
	Var_Dump::display($allUsers);
	echo '<br />';
}
	
// single user
echo 'This user will be removed:<br />';
$user = $admin->getUsers($removeUser);

if (!$user) {
	echo '<strong>Error</strong><br />';
} else {
	Var_Dump::display($user);
	echo '<br />';
}
	
// Remove
$removed = $admin->removeUser($removeUser);

if (!$removed) {
	echo '<strong>Error</strong><br />';
} else {
	echo $removeUser.' was deleted<br />';
}

// Update
$updated = $admin->updateUser($updateUser, 'updated_user', 'foo', array(), $custom);
if (!$updated) {
	echo '<strong>Error</strong><br />';
} else {
	echo $updateUser.' was updated<br />';
	$user = $admin->getUsers($updateUser);
	
	if (!$user) {
    	echo '<strong>Error</strong><br />';
	} else {
		Var_Dump::display($user);
		echo '<br />';
	}
}

// Get
echo 'All the users:<br />';
$allUsers = $admin->searchUsers();
if (!$allUsers) {
	echo '<strong>Error</strong><br />';
} else {
	Var_Dump::display($allUsers);
	echo '<br />';
}

unset($user);
echo 'Test fetching auth_user_id AND perm_user_id with PERM getUsers()<br />';
echo 'Auth<br />';
$filter = array('auth_user_id' => '1239');
$options = array('with_rights' => true);
$user = $admin->auth->getUsers($filter, $options);
if (!$users) {
	echo '<strong>Error</strong><br />';
} else {
	Var_Dump::display($user);
	echo '<br />';
}
unset($user);

echo 'Perm<br />';
$filter = array('perm_user_id' => '3');
$user = $admin->perm->getUsers($filter);
if (!$users) {
	echo '<strong>Error</strong><br />';
} else {
	Var_Dump::display($user);
	echo '<br />';
}

echo '<hr />';
	