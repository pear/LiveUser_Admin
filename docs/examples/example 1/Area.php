<?php
require_once 'index.php';
echo '<h3>Area</h3>';

$applications = $admin->perm->getApplications();
if  (empty($applications)) {
    echo 'Run the <strong>Application</strong> test first<br />';
    exit;
}

// Add
$id = array_rand($applications);
for ($i = 1; $i < 4; $i++) {
    $data = array(
        'application_id' => $applications[$id]['application_id'],
        'area_define_name' => 'AREA'.rand(),
    );
    $areaId  = $admin->perm->addArea($data);

    if ($areaId === false) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
        print_r($admin->getErrors());
    } else {
        echo 'Created Area Id <strong>' . $areaId . '</strong><br />';
    }
}

// Get
$areas = $admin->perm->getAreas();

if ($areas === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    print_r($admin->getErrors());
} elseif (empty($areas)) {
    echo 'No areas were found, thus we\'ve halted the rest of the test<br />';
} else {
    echo 'These are our current areas:';
    Var_Dump::display($areas);
    echo '<br />';

    // Remove
    $id = array_rand($areas);
    $filters = array('area_id' => $areas[$id]['area_id']);
    $rmArea = $admin->perm->removeArea($filters);

    if ($rmArea === false) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
        print_r($admin->getErrors());
    } else {
        echo '<strong>Area3</strong> was removed<br />';
        unset($areas[$id]);
    }

    // Update
    $id = array_rand($applications);
    $id2 = array_rand($areas);
    $data = array(
        'area_define_name' => 'AREA2_' . $areas[$id2]['area_id'] . 'updated',
        'application_id' => $applications[$id]['application_id'],
    );

    $id = array_rand($areas);
    $filters = array('area_id' => $areas[$id]['area_id']);
    $upArea = $admin->perm->updateArea($data, $filters);

    if ($upArea === false) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
        print_r($admin->getErrors());
    } else {
        echo '<strong>Area2</strong> was updated<br />';
        $params = array('filters' => array('area_id' => $areas[$id]['area_id']));
        $result = $admin->perm->getAreas($params);

        if ($result === false) {
            echo '<strong>Error on line: '.__LINE__.'</strong><br />';
            print_r($admin->getErrors());
        } elseif (empty($result)) {
            echo 'No areas were found<br />';
        } else {
            Var_Dump::display($result);
        }
    }

    // Test for area admins
    // Add
    $users = $admin->searchUsers();
    if ($users === false) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
        print_r($admin->getErrors());
    } elseif (empty($users)) {
        echo 'Please run the <a href="Users.php'.$qstring.'">Users</a> file to be able to test the area admin part.<br />';
    } else {
        for ($i = 0; $i < 15; $i++) {

            $id = array_rand($areas);
            $uid = array_rand($users);

            $data = array(
                'area_id' => $areas[$id]['area_id'],
                'perm_user_id' => $users[$uid]['perm_user_id']
            );
            $result = $admin->perm->addAreaAdmin($data);

            if ($result === false) {
                echo '<strong>Error on line: '.__LINE__.'</strong><br />';
                print_r($admin->getErrors());
            } else {
                echo 'perm user id <strong>' . $users[$uid]['perm_user_id'] . '</strong> was added as admin over area id <strong>' . $areas[$id]['area_id'] . '</strong><br />';
            }
        }

        // Remove
        // By area id
        $id = array_rand($areas);
        $filters = array(
            'area_id' => $areas[$id]['area_id']
        );

        $result = $admin->perm->removeAreaAdmin($filters);

        if ($result === false) {
            echo '<strong>Error on line: '.__LINE__.'</strong><br />';
            print_r($admin->getErrors());
        } else {
           echo 'Removed area admin with area id <strong>' . $areas[$id]['area_id'] . '</strong><br />';
        }

        unset($areas[$id]);

        // by right id
        $uid = array($users);
        $filters = array(
            'perm_user_id' => $users[$uid]['perm_user_id'],
        );
        $result = $admin->perm->removeAreaAdmin($filters);

        if ($result === false) {
            echo '<strong>Error on line: '.__LINE__.'</strong><br />';
            print_r($admin->getErrors());
        } else {
           echo 'Removed area admin with perm user id <strong>' . $users[$uid]['perm_user_id'] . '</strong><br />';
        }

        unset($users[$uid]);

        // by area and right id
        $id = array_rand($areas);
        $uid = array($users);
        $filters = array(
            'perm_user_id' => $users[$uid]['perm_user_id'],
            'area_id' => $areas[$id]['area_id']
        );

        $result = $admin->perm->removeAreaAdmin($filters);

        if ($result === false) {
            echo '<strong>Error on line: '.__LINE__.'</strong><br />';
            print_r($admin->getErrors());
        } else {
           echo 'Removed area admin with perm user id <strong>' . $users[$uid]['perm_user_id'] . '</strong>
                 and area id <strong>' . $areas[$id]['area_id'] . '</strong><br />';
        }

        unset($areas[$id]);
        unset($users[$uid]);
    }

    // Get
    $areas = $admin->perm->getAreas();

    if ($areas === false) {
        echo '<strong>Error on line: '.__LINE__.'</strong><br />';
        print_r($admin->getErrors());
    } elseif (empty($areas)) {
        echo 'No areas were found<br />';
    } else {
        echo 'These are our current areas:';
        Var_Dump::display($areas);
        echo '<br />';
    }
}
echo '<hr />';
