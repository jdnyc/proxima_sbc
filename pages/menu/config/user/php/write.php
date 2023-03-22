<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/common/db_connection.php';

switch($_REQUEST['action']) {
	case 'insert':
		user_insert();
	break;

	case 'update':
		user_update();
	break;
}

function user_insert() {

	$columns = array();
	$values = array();
	foreach($_REQUEST as $k=>$v) {
		if(preg_match('/^b/', $k)) {
			array_push($columns, strtolower(substr($k, 1)));
			array_push($values, "'".$v."'");
		}
	}

	$result = $db->exec("insert into users (".implode(', ', $columns).") values (".implode(', ', $values).")");

	echo json_encode(array(
		'success' => true
	));
}
?>