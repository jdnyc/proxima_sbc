<?php
session_start();
require_once ($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/workflow/lib/task_manager.php');

$content_id = $_POST['content_id'];
$channel = 'sgl_archive';
$job_priority = 1;
$user_id = $_SESSION['user']['user_id'];
$insert_task = new TaskManager($db);

$insert_task->start_task_workflow($content_id, $channel, $user_id);

echo json_encode(array(
	'success' => true,
	'msg' => '아카이브 요청이 완료되었습니다.'
));

?>