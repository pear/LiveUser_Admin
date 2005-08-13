<?php
require_once 'index.php';
echo '<h3>Test</h3>';

$filters = array('application_id' => '109');
$rmArea = $admin->perm->removeArea($filters);

if ($rmArea === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    print_r($admin->getErrors());
} else {
    echo '<strong>Area</strong> was removed<br />';
}