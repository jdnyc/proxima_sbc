<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$user_id = $_SESSION['user']['user_id'];
$category_ids = $_POST['category_ids'];
$action = $_POST['action'];

try
{
	if($user_id == '' || $user_id == 'temp')
	{
		throw new Exception('Please login again');
	}
	
	if(empty($category_ids))
	{
		throw new Exception('No category_id found.');
	}
	
	switch($action)
	{
		case 'accept':
			$status = 'accept';
		break;
		case 'decline':
			$status = 'decline';
		break;
		default:
		break;
	}	

	$query = "update bc_category_topic set
				status = '".$status."'
			   where category_id in (".implode(',', $category_ids).")";
	$db->exec($query);

	foreach($category_ids as $category_id) {
		insertLogTopic('topic '.$action, $user_id, $category_id, $category_id.' 토픽 '.$action);
	}

	echo json_encode(array(
		"success" => true,
		"query" => $query,
		"msg" => 'OK'
	));
}
catch(Exception $e)
{
	echo json_encode(array(
		"success" => false,
		"lastquery" => $db->lastquery,
		"msg" => $e->getMessage()
	));
}



?>