<?php require_once 'index.php'; ?>
<h3>Rights</h3>
<?php
$areas = $admin->perm->getAreas();
if  (empty($areas)) {
    echo 'Run the <b>Area</b> test first<br />';
    exit;
}
// Add
if ($areas === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br (>';
}

foreach ($areas as $row) {
    for ($i = 1; $i < 20; $i++) {
        $data = array(
            'area_id' => $row['area_id'],
            'right_define_name' => 'RIGHT_' . $row['area_id'] . '_' . rand(),
        );
        $rightId = $admin->perm->addRight($data);
        if ($rightId === false) {
              echo '<strong>Error on line: '.__LINE__.'</strong><br />';
        } else {
            echo 'Created Right Id <b>'.$rightId.'</b><br />';
        }
    }
}

// Get
$rights = $admin->perm->getRights();

if ($rights === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    echo 'These are our current rights:';
    Var_Dump::display($rights);
    echo '<br />';
}

// Remove
$id = array_rand($rights);
$filters = array('right_id' => $rights[$id]['right_id']);
$rmRight = $admin->perm->removeRight($filters);

if ($rmRight === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    echo '<b>Right_' . $id . '</b> was removed<br />';
}

// Update
$id = array_rand($rights);
$data = array('right_define_name' => 'RIGHT_' . $id . '_UPDATED');
$filters = array('right_id' => $rights[$id]['right_id']);
$upRight = $admin->perm->updateRight($data, $filters);
if ($upRight === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    echo '<b>Right_'. $id .'</b> was updated<br />';
    $params = array('filters' => array('right_id' => $rights[$id]['right_id']));
    $result = $admin->perm->getRights($params);

    if ($result === false) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    } else {
        Var_Dump::display($result);
    }
}

// Get
$rights = $admin->perm->getRights();

if ($rights === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
} else {
    echo 'These are our current rights:';
    Var_Dump::display($rights);
    echo '<br />';
}
echo '<hr />';
