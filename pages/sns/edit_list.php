<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/SNS.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

try
{
	$mode = $_POST['mode'];
	$arr_sns_seq_no = $_POST['sns_seq_no_arr'];
	$user_id = $_SESSION['user']['user_id'];

	switch($mode)
	{
		case 'delete':
			$sns = new SNS($db);
			foreach($arr_sns_seq_no as $sns_seq_no) {
				$sns_info = $db->queryRow("
					SELECT	*
					FROM	BC_SOCIAL_TRANSFER
					WHERE	SNS_SEQ_NO=".$sns_seq_no."
				");
				$content_id = $sns_info['content_id'];

				$cur_time = date('YmdHis');

				
				$task_mgr = new TaskManager($db);
				$channel = 'sns_delete';
				$new_task_id = $task_mgr->start_task_workflow($content_id, $channel, $user_id);

				$update_data_sns = array(
					'task_id' => $new_task_id,
					'status' => 'REQUEST'
				);
				$where = " sns_seq_no=".$sns_seq_no." ";
				$db->update('BC_SOCIAL_TRANSFER', $update_data_sns, $where);

				$sns->delete($new_task_id);
			}
		break;
	}

	echo json_encode(array(
		'success' => true,
		'data' => $data
	));
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

?>