<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

try
{
		
	insertWorkList();

	echo json_encode(array(
		'success' => true,
		'msg' => '등록되었습니다'
	));
	
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage() 
	));
}

function insertWorkList(){
	global $db;

	$work_title = $db->escape( $_POST['awesomeUploader_title'] );
	$work_type = $_POST['awesomeUploader_type'];
	$created_date = date("YmdHis");

	$status				= 'complete';//이미 파일등록
	$connote			= $db->escape( $_POST['awesomeUploader_connote'] );

	$from_user_id		= $_POST['awesomeUploader_user_id'];
	$nps_work_list_id	= getSequence('SEQ_NPS_WORK_LIST_ID');
	$category_id		= $_POST['awesomeUploader_category_id'];
	//$member_group_id	= $_POST['awesomeUploader_category_id'];

	$category = $db->queryRow("select * from bc_category where category_id='$category_id'");
	
	$parent_id = $category['parent_id'];

	$member_group_id = $db->queryOne("select member_group_id from path_mapping where category_id='$parent_id'");

	$file_path = '';
	//NPS_WORK_LIST_ID
	//FROM_USER_ID
	//CONTENT_ID
	//WORK_TYPE
	//CONNOTE
	//STATUS
	//CREATED_DATE
	//WORK_TITLE
	//FILE_PATH
	//TO_USER_ID
	//CATEGORY_ID
	//MEMBER_GROUP_ID
	//WORK_START_DATE
	//WORK_END_DATE
	//IS_SEND_TO
	$query = "insert into NPS_WORK_LIST (NPS_WORK_LIST_ID,	FROM_USER_ID, WORK_TYPE, CONNOTE, STATUS, CREATED_DATE, WORK_TITLE, FILE_PATH, CATEGORY_ID, MEMBER_GROUP_ID ) values ($nps_work_list_id, '$from_user_id', '$work_type', '$connote', '$status', '$created_date', '$work_title','$file_path','$category_id','$member_group_id') ";
	$r = $db->exec($query);
	
}


?>