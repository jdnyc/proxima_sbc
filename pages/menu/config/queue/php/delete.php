<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$r = $db->exec("update queue set status='" . $_POST['status'] . "' where id=" . $_POST['id']);

echo json_encode(array(
	'success' => true
));
?>