<?php
session_start();
/*
2012/03/08
edit by 김성민
::본 페이지는 파일러 등록 전용 페이지로만 사용하도록 수정::
*/
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/MetaData.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/soap/nusoap.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/Search.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

//$receive_xml = iconv('euc-kr', 'utf-8', file_get_contents('php://input'));

$log_path = $_SERVER['DOCUMENT_ROOT'].'/log/register_sequence22_'.date('Ymd').'.log';

$path = $_POST['path'];
$content_id = $_POST['content_id'];

file_put_contents($log_path, date("Y-m-d H:i:s\t").print_r($_REQUEST,true)."\n\n", FILE_APPEND);
file_put_contents($log_path, date("Y-m-d H:i:s\t")."content_id : ".$content_id .", path : ".$path."\n\n", FILE_APPEND);

try
{		
	//$path = "100382_Test_아카이브작업본.mxf";
	//$content_id = 100382;

	global $db;


	/**
		원본 있는지 없는지 확인
	

	$ori_del_check_q ="
		select count(*)
		from   bc_media 
		where  content_id = {$content_id}
			   and media_type = 'original'
			   and status = 0
	";

	$ori_cnt = $db->queryOne($ori_del_check_q);

	if($ori_cnt>0)
	{
		echo json_encode(array(
			'success' => false,
			'task_id' => $task_id,
			'msg' => '이미 작업 원본이 등록되어있습니다.'
		));

		return;
	}
	*/
		
	$this_workflow_channel = 'fileingest';
	$user_id = "admin";

	$task = new TaskManager($db);
	$task_id = $task->insert_task_query_outside_data($content_id, $this_workflow_channel, 1, $user_id, $path);

	echo json_encode(array(
		'success' => true,
		'task_id' => $task_id,
		'msg' => $e->getMessage()
	));

}
catch (Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

