<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');


$user_id = $_POST['user_id'];
$filename = $_POST['filename'];	
$filesize = $_POST['filesize'];	
$work_type = $_POST['type'];
$worker_id = $_POST['worker_id'];

$work_title = $_POST['title'];

try{

	print_r($_POST);
	exit;

	$from_user_id = $_SESSION['user']['user_id'];
	$nps_work_list_id	= getSequence('SEQ_NPS_WORK_LIST_ID');

	$query = "insert into nps_work_list (nps_work_list_id, from_user_id, content_id, work_type, connote, status, created_data, work_title, file_path, to_user_id) values ($nps_work_list_id, '$from_user_id', '$content_id', '$work_type', '$connote', '$status', '$created_data','$work_title','$file_path') ";


	$result = $db->exec($query);

//	addFile($content_id , $filename, $filesize );

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




//function addFile($content_id , $filename, $filesize ){
//	global $db;

//	$from_user_id = $user_id;
//	$nps_work_list_id = 1;

//	$created_data = $db->queryOne("select created_data from bc_content where content_id = '$content_id'");


//	$content_info = $db->queryRow("select * from bc_content where content_id = '$content_id' ");

//	$ori_info = $db->queryRow("select * from bc_media where media_type='original' and content_id='$content_id' ");

//	$ori_path_array = explode('/', $ori_info['path']);

//	$ori_filename = array_pop($ori_path_array);
//	$ori_path = implode('/', $ori_path_array);

//	$path = $db->escape($ori_path.'/Attach/'.$filename);

//	$storage_id = $ori_info['storage_id'];

//	$query = "insert into nps_work_list (content_id, storage_id, media_type, path, filesize, created_date, reg_type) values ($content_id, '$storage_id', '$type', '$path', '$filesize', '$cur_time', '$register') ";

//	$query = "insert into nps_work_list (nps_work_list_id, from_user_id, content_id, work_type, connote, status, created_data, work_title, file_path, to_user_id) values ($nps_work_list_id, '$from_user_id', '$content_id', '$work_type', '$connote', '$status', '$created_data','$work_title','$file_path') ";


//	$result = $db->exec($query);

//	$m_info = $db->queryRow("select media_id from bc_media where media_type='$type' order by media_id desc");

//	$channel = $register.'_'.$content_info['ud_content_id'];

//	insert_task_query($content_id, $filename, $path, $cur_time, $channel, $m_info['media_id'] );

//	return true;
//}

?>