<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/db.php';
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

$type 	  = $_REQUEST['type'];
$progress = $_REQUEST['progress'];
$task_id  = $_REQUEST['task_id'];
$target   = $_REQUEST['target'];
$source   = $_REQUEST['source'];
$content_id   = $_REQUEST['content_id'];

$request_ip = $_SERVER['REMOTE_ADDR'];

$task_type = "AME";

try {

	if($type =='update')
	{
		$ck_task_q = "
			select count(*)
			from   bc_task
			where  task_id = {$task_id}
		";

		$ck_cnt = $db->queryOne($ck_task_q);
		if($ck_cnt > 0 )
		{
			$progress = $progress ? $progress : '0';

			$ud_task_q = "
				update bc_task
				set    status = 'processing'
				      ,progress = {$progress}
				      ,source = '{$source}'
				      ,target = '{$target}'
				where  task_id = {$task_id}
			";

			$db->exec($ud_task_q);
		}
		else 
		{
			$media_id = $db->queryOne("select media_id from bc_media where content_id = {$content_id} and media_type = 'pr_sequence'");

			//echo "select media_id from bc_media where content_id = {$content_id} and media_type = 'pr_sequence'";
			//echo $media_id;
			$media_id =  $media_id ? $media_id : '0';
			$insert_data = array(
				'task_id' => $task_id,				
				'media_id' =>$media_id,
				'type' => $task_type,
				'status' => 'processing',				
				'source' => $source,
				'target' => $target,
				'parameter' => '""',
				'priority'=>100,
				'creation_datetime'=>date('YmdHis'),
				'start_datetime'=>date('YmdHis'),
				'task_workflow_id'=>9602,
				'task_user_id'=>'ame',
				'assign_ip' =>$request_ip
			);
			

			$db->insert('BC_TASK', $insert_data);			
		}

		echo json_encode(array(
			'success' => true,
			'msg' => '성공',
			'task_id'=>$task_id
		));
		
	}
	else if($type == 'complete' || $type == 'error')
	{
		$ck_task_q = "
			select count(*)
			from   bc_task
			where  task_id = {$task_id}
		";

		$ck_cnt = $db->queryOne($ck_task_q);
		if($ck_cnt > 0 )
		{
			if($type == 'error')
			{
				$progress = 0;
			}
			else 
			{
				$progress = 100;
			}

			$ud_task_q = "
				update bc_task
				set    status = '{type}'
				      ,progress = {$progress}
				      ,source = '{$source}'
				      ,target = '{$target}'
				where  task_id = {$task_id}
			";
			

			$db->exec($ud_task_q);

			/**
				Workflow GOGOGO GPU Transcoding!!! 
			*/

			if($type == 'complete')
			{
				///
				$this_workflow_channel = 'ame_ingest';
				$user_id = "admin";

				//$target = "O:\ONLINE\Storage\ame\증권-김지윤-수정본1 편집자_증권-김지윤-수정본1 편집자_3.mxf";

				$target = str_replace("O:\\ONLINE\\Storage\\ame\\","",$target);

				$task = new TaskManager($db);
				$task_id = $task->insert_task_query_outside_data($content_id, $this_workflow_channel, 1, $user_id, $target);

			}

			echo json_encode(array(
				'success' => true,
				'msg' => '성공'
			));

		}
		else 
		{
			echo json_encode(array(
				'success' => false,
				'msg' => '성공'
			));
		}
	}

	
}
catch ( Exception $e )
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

?>
