<?php require_once 'index.php'; ?>
<h3>Application</h3>
<?php
// Add
for ($i = 1; $i < 4; $i++) {
    $data = array('application_define_name' => 'APP'.rand());
    $appId = $admin->perm->addApplication($data);

    if ($appId === false) {
        echo '<strong>Error on line: '.__LINE__.' last query: '.$admin->perm->_storage->dbc->last_query.'</strong><br />';
        print_r($admin->getErrors());
    } else {
        echo 'Created Application id <b>' . $appId . '</b><br />';
    }
}

// Get
$currentApps = $admin->perm->getApplications();

if ($currentApps === false) {
    echo '<strong>Error on line: '.__LINE__.' last query: '.$admin->perm->_storage->dbc->last_query.'</strong><br />';
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

if ($removeApp === false) {
    echo '<strong>Error on line: '.__LINE__.' last query: '.$admin->perm->_storage->dbc->last_query.'</strong><br />';
} else {
    echo '<b>App3</b> was removed<br />';
    unset($currentApps[$id]);
}

// Update
$id = array_rand($currentApps);
$data = array('application_define_name' => 'APP2_' . $currentApps[$id]['application_id'] . 'updated');
$filters = array('application_id' => $currentApps[$id]['application_id']);
$updateApp = $admin->perm->updateApplication($data, $filters);

if ($updateApp === false) {
    echo '<strong>Error on line: '.__LINE__.' last query: '.$admin->perm->_storage->dbc->last_query.'</strong><br />';
} else {
    echo '<b>App2</b> was updated<br />';
    $params = array('filters' => array('application_id' => $currentApps[$id]['application_id']));
    $result = $admin->perm->getApplications($params);

    if ($result === false) {
        echo '<strong>Error on line: '.__LINE__.' last query: '.$admin->perm->_storage->dbc->last_query.'</strong><br />';
    } else {
        Var_Dump::display($result);
    }
}

// Get
$currentApps = $admin->perm->getApplications();

if ($currentApps === false) {
    echo '<strong>Error on line: '.__LINE__.' last query: '.$admin->perm->_storage->dbc->last_query.'</strong><br />';
} else {
    echo 'These are our current applications:';
    Var_Dump::display($currentApps);
    echo '<br />';
}
echo '<hr />';
