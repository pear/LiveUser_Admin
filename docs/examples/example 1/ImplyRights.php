<?php require_once 'index.php'; ?>
<h3>ImplyRights</h3>
<?php
$currentRight = $admin->perm->getRights();
if  (empty($currentRight)) {
	echo 'Run the <b>Right</b> test first<br />';
	exit;
}
echo '<hr />';