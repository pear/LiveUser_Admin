<?php require_once 'index.php'; ?>
<h3>Application</h3>
<?php
// Add
for ($i = 1; $i < 4; $i++) {
    $data = array('application_define_name' => 'APP' . $i);
    $appAdd = $admin->perm->addApplication($data);

    if (!$appAdd) {
        echo '<strong>Error</strong><br />';
        print_r($admin->getErrors());
    } else {
        echo 'Added application <b>App'.$i.'</b><br />';
    }
}

// Get
$currentApps = $admin->perm->getApplications();

if (PEAR::isError($currentApps) || !$currentApps) {
    echo '<strong>Error</strong><br />';
    print_r($admin->getErrors());
} else {
    echo 'These are our current applications:';
    Var_Dump::display($currentApps);
    echo '<br />';
}

// Set/Get current Application
$id = array_rand($currentApps);
$admin->perm->setCurrentApplication($currentApps[$id]['application_id']);
$currentApp = $admin->perm->getCurrentApplication();
echo $currentApp.' is our current application now.<br />';

// Remove
$id = array_rand($currentApps);
$filters = array('application_id' => $currentApps[$id]['application_id']);
$removeApp = $admin->perm->removeApplication($filters);

if (!$removeApp) {
    echo '<strong>Error</strong><br />';
} else {
    echo 'App3 was removed<br />';
}

// Update
$id = array_rand($currentApps);
$data = array('application_define_name' => 'APP2_' . $currentApps[$id]['application_id'] . 'updated');
$filters = array('application_id' => $currentApps[$id]['application_id']);
$updateApp = $admin->perm->updateApplication($data, $filters);

if (!$updateApp) {
    echo '<strong>Error</strong><br />';
} else {
    echo 'App2 was updated<br />';
    $params = array('filters' => array('application_id' => $currentApps[$id]['application_id']));
    $result = $admin->perm->getApplications($params);

    if (!$result) {
        echo '<strong>Error</strong><br />';
    } else {
        Var_Dump::display($result);
    }
}

// Get
$currentApps = $admin->perm->getApplications();

if (!$currentApps) {
    echo '<strong>Error</strong><br />';
} else {
    echo 'These are our current applications:';
    Var_Dump::display($currentApps);
    echo '<br />';
}
echo '<hr />';
