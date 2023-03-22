<?php
session_start();
require_once ($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/workflow/lib/task_manager.php');

//2014.01.08 신규(임찬모)
//기존 mxf나 mov가 있어서 리스토어가 안될경우 사용자가 수동으로 요청하여 삭제하고 리스토어 되는 로직

$job_priority = 1;
$user_id = $_SESSION['user']['user_id'];
$insert_task = new TaskManager($db);

foreach ($data as $content_id) {
	$query = "select ud_content_id from bc_content where content_id = '$content_id'";
	$check_ud_content = $db->queryOne($query);

	if($check_ud_content == '358')
	{
                $channel = 'restore_manual_nps';
	}
	else if($check_ud_content == '334')
	{
//		$channel = 'news_archive';
	}
	else if($check_ud_content == '374')
        {
//                $channel = 'tape_hd_archive';
        }
        else if($check_ud_content == '202')
        {
//                $channel = 'tape_sd_archive';
        }
        else
	{
//		$channel = 'sgl_archive';
	}
	$insert_task->start_task_workflow($content_id, $channel, $user_id);
}

echo json_encode(array(
	'success' => true,
	'msg' => '메뉴얼 아카이브 요청이 완료되었습니다.'
));
