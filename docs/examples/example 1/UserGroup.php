<?php require_once 'index.php'; ?>
<h3>UserGroup</h3>
<?php
$currentGroup = $admin->perm->getGroups();
if  (empty($currentGroup)) {
	echo 'Run the <b>Group</b> test first<br />';
	exit;
}

$currentUser = $admin->searchUsers();
if  (empty($currentUser)) {
	echo 'Run the <b>User</b> test first<br />';
	exit;
}
// Add
$users   = $admin->searchUsers();
$groups = $admin->perm->getGroups();

foreach ($groups as $group) {
	$rand = array_rand($users);
	$return = $admin->perm->addUserToGroup($users[$rand]['auth_user_id'], $group['group_id']);
	
	if (!$return) {
	    echo '<strong>Error</strong><br />';
	} else {
		echo $users[$rand]['name'].' was added to group <b>'.$group['group_id'].'</b><br />';
	}
}
// Get users from one group
$group = array_rand($groups);

$params = array(
    'filters' => array(
        'group_id' => $groups[$group]['group_id']
    )
);
$usersGroup = $admin->perm->getUsersFromGroup($params);

if (!$usersGroup) {
    echo '<strong>Error</strong><br />';
} else {
	echo 'Perm ID\'s of the users in group '.$groups[$randGroup]['group_id'].'<br />';
	Var_Dump::display($usersGroup);
	echo '<br />';
}

// Remove user from one group
$group = array_rand($groups);
$user = array_rand($users);

$filters = array(
    'group_id' => $groups[$group]['group_id'],
    'perm_user_id' => $users[$user]['perm_user_id']
);
$removed = $admin->perm->removeUserFromGroup($filters);

if (!$removed) {
    echo '<strong>Error</strong><br />';
} else {
	echo $users[$user]['name'].' was removed from group <b>'.$groups[$group]['group_id'].'</b><br />';
}

// Remove user from all his groups
$randUser = array_rand($users);

$removed = $admin->perm->removeUserFromGroup($randUser);

if ($removed) {
    echo '<strong>Error</strong><br />';
} else {
	echo $users[$randUser]['name'].' was removed from <b>ALL</b> his groups<br />';
}

// Get users from all groups
foreach ($groups as $group) {
    $params = array(
        'filters' => array(
            'group_id' => $group['group_id']
        )
    );
	$usersGroup = $admin->perm->getGroups($params);
	
	if (!$usersGroup) {
        echo '<strong>Error</strong><br />';
	} else {
		echo 'Perm ID\'s of the users in group '.$group['group_id'].'<br />';
		Var_Dump::display($usersGroup);
		echo '<br />';
	}
}
echo '<hr />';
