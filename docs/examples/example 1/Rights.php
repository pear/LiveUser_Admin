<?php require_once 'index.php'; ?>
<h3>Rights</h3>
<?php
$currentArea = $admin->perm->getAreas();
if  (empty($currentArea)) {
    echo 'Run the <b>Area</b> test first<br />';
    exit;
}
// Add
$areas = $admin->perm->getAreas();
if ($areas === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br (>';
}

foreach ($areas as $row) {
    for ($i = 1; $i < 20; $i++) {
        $data = array(
            'area_id' => $row['area_id'],
            'right_define_name' => 'RIGHT_' . $row['area_id'] . '_' . rand(),
        );
        $right_id = $admin->perm->addRight($data);
        if ($right_id === false) {
              echo '<strong>Error on line: '.__LINE__.' last query: '.$admin->perm->_storage->dbc->last_query.'</strong><br />';
        } else {
            echo 'Added right<br />';
        }
    }
}

// Get
$currentRights = $admin->perm->getRights();

if ($currentRights === false) {
    echo '<strong>Error on line: '.__LINE__.' last query: '.$admin->perm->_storage->dbc->last_query.'</strong><br />';
} else {
    echo 'These are our current rights:';
    Var_Dump::display($currentRights);
    echo '<br />';
}

// Remove
$id = array_rand($currentRights);
$filters = array('right_id' => $currentRights[$id]['right_id']);
$rmRight = $admin->perm->removeRight($filters);

if ($rmRight === false) {
    echo '<strong>Error on line: '.__LINE__.' last query: '.$admin->perm->_storage->dbc->last_query.'</strong><br />';
} else {
    echo 'Right_' . $id . ' was removed<br />';
}

// Update
$id = array_rand($currentRights);
$data = array('right_define_name' => 'RIGHT_' . $id . '_UPDATED');
$filters = array('right_id' => $currentRights[$id]['right_id']);
$upRight = $admin->perm->updateRight($data, $filters);
if ($upRight === false) {
    echo '<strong>Error on line: '.__LINE__.' last query: '.$admin->perm->_storage->dbc->last_query.'</strong><br />';
} else {
    echo 'Right_'. $id .' was updated<br />';
    $params = array('filters' => array('right_id' => $currentRights[$id]['right_id']));
    $result = $admin->perm->getRights($params);

    if ($result === false) {
        echo '<strong>Error on line: '.__LINE__.' last query: '.$admin->perm->_storage->dbc->last_query.'</strong><br />';
    } else {
        Var_Dump::display($result);
    }
}

// Get
$currentRights = $admin->perm->getRights();

if ($currentRights === false) {
    echo '<strong>Error on line: '.__LINE__.' last query: '.$admin->perm->_storage->dbc->last_query.'</strong><br />';
} else {
    echo 'These are our current rights:';
    Var_Dump::display($currentRights);
    echo '<br />';
}
echo '<hr />';
