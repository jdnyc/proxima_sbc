<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

try
{
	$content_id = $_POST['content_id'];

	$ud_content_id	= $db->queryOne("select ud_content_id from bc_content where content_id=".$content_id);

	$created_time = date('YmdHis');
	$register = $_SESSION['user']['user_id'];
	$storage_id = 0;
//	$media_type = 'Rewrap '.$rewrap_cnt;

	$source = $db->queryOne("select path from bc_media where media_type='original' and content_id=".$content_id);
	$target = dirname($source);

	$channal = 'TRANS_'.$ud_content_id;

	// 작업 등록
	insert_task_query($content_id, $source, $target, $created_time, $channal);

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