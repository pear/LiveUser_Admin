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

    if (!$granted) {
		echo '<strong>Error</strong><br />';
	} else {
		echo $group.' was granted the right <b>'.$right.'</b><br />';
	}
}
echo '<hr />';
$group = array_rand($groups);
$params = array(
	'fields' => array(
	    'right_id',
	    'right_define_name',
	),
	'with' => array(
	    'group_id' => array(
	        'group_id',
	    ),
	),
    'filters' => array(
        'group_id' => $groups[$group]['group_id']
    ),
    'limit' => 10,
    'offset' => 0,
);
$allGroupRights = $admin->perm->getRights($params);
echo '<br />' . $admin->perm->_storage->dbc->last_query;
if (!$allGroupRights) {
	echo '<strong>Error</strong><br />';
} else {
	echo '<hr />Here are all the group rights for the group ' . $groups[$group]['group_id'] . ':<br />';
	Var_Dump::display($allGroupRights);
	echo '<br />';
}

/*
$right   = array_rand($rights);
$group = array_rand($groups);
$filters = array(
    'right_id' => $rights[$right]['right_id'],
    'group_id' => $groups[$group]['group_id']
);
$removed = $admin->perm->revokeGroupRight($filters);

if (!$removed) {
	echo '<strong>Error</strong><br />';
} else {
	echo 'Removed the right <b>'.$right.'</b> on group <b>'.$group.'</b><br />';
}

$right   = array_rand($rights);
$group = array_rand($groups);
$data = array('right_level' => 2);
$filters = array(
    'right_id' => $rights[$right]['right_id'],
    'group_id' => $groups[$group]['group_id']
);
$updated = $admin->perm->updateGroupRight($data, $filters);

if (!$updated) {
	echo '<strong>Error</strong><br />';
} else {
	echo 'Updated the right level of <b>'.$group.'</b><br />';
	$params = array(
	    'fields' => array(
	        'right_id'
	    ),
	    'with' => array(
            'group_id' => array(
                'fields' => array(
                    'group_id',
                ),
            ),
        ),
	    'filters' => array(
	        'right_id' => $rights[$right]['right_id'],
	        'group_id' => $groups[$group]['group_id']
	    )
	);
	#$result = $admin->perm->getRights($params);

	if (!$result) {
        echo '<strong>Error</strong><br />';	
	} else {
	    Var_Dump::display($result);
	}
}

$params = array(
    'fields' => array(
        'right_id',
    ),
    'with' => array(
        'group_id' => array(
            'group_id',
            'right_level',
        ),
    ),
);
$allGroups = $admin->perm->getRights($params);
echo 'Here are all the group rights after the changes:<br />';
if (!$allGroups) {
    echo '<strong>Error</strong><br />';	
} else {
    Var_Dump::display($allGroups);
}
echo $admin->perm->_storage->dbc->last_query;*/
echo '<br />';
echo '<hr />';
