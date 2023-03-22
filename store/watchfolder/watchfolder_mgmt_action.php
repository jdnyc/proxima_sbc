<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

try
{

	$content_id = $_REQUEST['content_id'];
	$db->exec("UPDATE BC_CONTENT SET STATUS='2' WHERE CONTENT_ID=".$content_id);
	searchUpdate($content_id);

	echo json_encode(array(
		'success' => true
	));
}
catch (Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}


?>