<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/common/db_connection.php';


try 
{
	$rows = $db->queryAll('select * from users order by '.$_REQUEST['sort'].' '.$_REQUEST['dir']);

	echo json_encode(array(
		'success' => true,
		'total' => count($rows),
		'data' => $rows
	));
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'message' => $e->getMessage()
	));
}

?>