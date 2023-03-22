<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/db.php');

$created_time =date('YmdHis');
$channel = 'das_web';
$user_id = $_SESSION['user']['user_id'];

try
{
	$items = json_decode(urldecode($_POST['values']));

	$contents_id		= $items->k_contents->contents_id;

	executeQuery(sprintf("update content set status='%s' where content_id in (%s)",
									CONTENT_STATUS_REFUSE,
									$contents_id));

	die(json_encode(array(
		'success' => true
	)));
}
catch (Exception $e)
{
	die(json_encode(array(
		'success' => false,
		'msg' => $e->getMessage(),
		'query' => $db->last_query
	)));
}
?>