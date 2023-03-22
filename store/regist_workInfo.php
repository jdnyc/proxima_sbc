<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');


$user_id = $_POST['user_id'];
$work_type = $_POST['type'];
$worker_id = $_POST['worker_id'];

$work_title = $_POST['work_title'];

try{

	$from_user_id = $user_id;

	$nps_work_list_count = $db->queryOne("select max(nps_work_list_id) from nps_work_list");
	$nps_work_list_id =$nps_work_list_count+1; 

	$query = "insert into nps_work_list (nps_work_list_id, from_user_id, content_id, work_type, connote, status, created_date, work_title, file_path, to_user_id) values ('$nps_work_list_id', '$from_user_id', '$content_id', '$work_type', '$connote', '$status', '$created_date','$work_title','$file_path','$worker_id') ";


	$result = $db->exec($query);

	echo json_encode(array(
		'success' => true,
		'msg' => $result
	));
	
}catch(Exception $e){
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage() 
	));
}
?>