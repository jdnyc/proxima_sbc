<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

try
{
	$type = $_POST['type'];
	$content_ids = $_POST['records'];

	$task_user_id = $_SESSION['user']['user_id'];

	if( empty($content_ids) ) throw new Exception('레코드 정보가 없습니다');	

	if( !( $content_ids = json_decode($content_ids, true) ) ) throw new Exception('디코딩 실패');
	
		
	switch($type)
	{
		case 'ToCompletionEditing':		
	
			foreach($content_ids as $content_id)
			{
				$channel = 'out_comp_edit';				
				$task = new TaskManager($db);
				$task->start_task_workflow($content_id, $channel, $task_user_id );
			}

		break;

		case 'ToDMC':
			$created_date = date("YmdHis");
			foreach($content_ids as $content_id)
			{

				$channel = 'out_to_DMC';				
				$task = new TaskManager($db);
				$task->start_task_workflow($content_id, $channel, $task_user_id );

				$task_id = $task->get_task_id();
				
				$r = $db->exec("insert into DMC_LINK_LIST  (CONTENT_ID,STATUS,USER_ID,REG_DATE,LINK_TYPE,TASK_ID) values ('$content_id','queue','$task_user_id', '$created_date' ,'TM', '$task_id')");
			}
		break;

		case 'ToDAS':
			$created_date = date("YmdHis");
			foreach($content_ids as $content_id)
			{

				$channel = 'out_to_DAS';				
				$task = new TaskManager($db);
				$task->start_task_workflow($content_id, $channel, $task_user_id );

				$task_id = $task->get_task_id();
				
				$r = $db->exec("insert into DAS_LINK_LIST  (CONTENT_ID,STATUS,USER_ID,REG_DATE,LINK_TYPE,TASK_ID) values ('$content_id','queue','$task_user_id', '$created_date' ,'TM', '$task_id')");
			}
		break;

		default:

		break;
	
	}

	echo json_encode(array(
		'success' => true,
		'msg' => '전송작업 등록 성공'
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