<?php require_once 'index.php'; ?>
<h3>Subgroups</h3>
<?php
$groups = $admin->perm->getGroups();
if  (empty($groups)) {
    echo 'Run the <b>Group</b> test first<br />';
    exit;
}

for ($i = 0; $i < 5; $i++) {
    $group = array_rand($groups);
    $subgroup = array_rand($groups);
    $assign = $admin->perm->assignSubgroup($group, $subgroup);
    if (PEAR::isError($assign)) {
        echo $assign->getMessage().'<br />';
    } elseif ($assign === false) {
        echo $subgroup.' is already a subgroup of <b>'.$group.'</b><br />';
    } else {
        echo $subgroup.' is now subgroup of <b>'.$group.'</b><br />';
    }
}

# unassignSugroup
# getParentGroup

// Get
echo 'All the groups:<br />';
$allGroups = $admin->perm->getGroups(null, true);
if (PEAR::isError($allGroups)) {
    echo $allGroups->getMessage().'<br />';
} else {
    Var_Dump::display($allGroups);
    echo '<br />';
}
echo '<hr />';