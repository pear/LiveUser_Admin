<?php require_once 'index.php'; ?>
<h3>Area</h3>
<?php
$currentApps = $admin->perm->getApplications();
if  (empty($currentApps)) {
    echo 'Run the <b>Application</b> test first<br />';
    exit;
}

// Add
$id = array_rand($currentApps);
for ($i = 1; $i < 4; $i++) {
    $data = array(
        'application_id' => $currentApps[$id]['application_id'],
        'area_define_name' => 'AREA'.rand(),
    );
    $areaAdd  = $admin->perm->addArea($data);

    if (!$areaAdd) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    } else {
        echo 'Added area<br />';
    }
}

// Get
$currentAreas = $admin->perm->getAreas();

if (!$currentAreas) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    echo 'These are our current areas:';
    Var_Dump::display($currentAreas);
    echo '<br />';
}

// Remove
$id = array_rand($currentAreas);
$filters = array('area_id' => $currentAreas[$id]['area_id']);
$rmArea = $admin->perm->removeArea($filters);

if (!$rmArea) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    echo 'Area3 was removed<br />';
    unset($currentAreas[$id]);
}

// Update
$id = array_rand($currentApps);
$id2 = array_rand($currentAreas);
$data = array(
    'area_define_name' => 'AREA2_' . $currentAreas[$id2]['area_id'] . 'updated',
    'application_id' => $currentApps[$id]['application_id'],
);

$id = array_rand($currentAreas);
$filters = array('area_id' => $currentAreas[$id]['area_id']);
$upArea = $admin->perm->updateArea($data, $filters);

if (!$upArea) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    echo 'Area2 was updated<br />';
    $params = array('filters' => array('area_id' => $currentAreas[$id]['area_id']));
    $result = $admin->perm->getAreas($params);

    if (!$result) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    } else {
        Var_Dump::display($result);
    }
}

// Get
$currentAreas = $admin->perm->getAreas();

if (!$currentAreas) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    echo 'These are our current areas:';
    Var_Dump::display($currentAreas);
    echo '<br />';
}
echo '<hr />';
