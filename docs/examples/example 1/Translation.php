<?php
require_once 'index.php';
echo '<h3>Translation</h3>';

// Get
echo 'All the groups:<br />';
$groups = $admin->perm->getGroups(
    array(
        'fields' => array('group_id'),
        'select' => 'col'
    )
);

if (empty($groups)) {
    echo 'Run the <strong>Group</strong> test first<br />';
    exit;
}

$admin->perm->removeTranslation(array('section_type' => LIVEUSER_SECTION_GROUP));

foreach ($groups as $group_id) {
    $data = array(
        'section_id' => $group_id,
        'section_type' => LIVEUSER_SECTION_GROUP,
        'language_id' => 'de',
        'name' => 'Name of '.$group_id.'is '.md5(uniqid(rand())),
        'description' => 'Description of '.$group_id.'is '.md5(uniqid(rand())),
    );
    $translationId = $admin->perm->addTranslation($data);
}

// Get
echo 'All the groups with translation:<br />';

$groups = $admin->perm->getGroups(array('fields' => array('group_id', 'name', 'description')));
if ($groups === false) {
    echo '<strong>Error on line: '.__LINE__.'</strong><br />';
    print_r($admin->getErrors());
} elseif (empty($groups)) {
    echo 'No groups were found<br />';
} else {
    Var_Dump::display($groups);
    echo '<br />';
}

echo '<hr />';
