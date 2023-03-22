<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
$content_id = $_POST['content_id'];
$user_id = $_SESSION['user']['user_id'];
$created_time =date('YmdHis');

try
{

	$regist_update_query = $db->exec("update bc_content set status= '".CONTENT_STATUS_COMPLETE."' where content_id='".$content_id."'");

	insertLog('accept', $user_id, $content_id, '승인');


	if($regist_update_query)
	{
		$data = array(
			'success'	=> true
		);
	}
	else
	{
		$data = array(
			'success'	=>	false,
			'msg'		=>	'승인처리 실패'
		);
	}

	echo json_encode($data);
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}
?>