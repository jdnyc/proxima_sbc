<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/SNS.class.php');

try
{
	$sns = new SNS($db);
	$sns->_log('updateStatusPOST:'.print_r($_POST,true));

	$request = $_POST['request'];
	$request_arr = json_decode($request, true);
	//$sns->_log('updateStatusPOST JSON parse:'.print_r($request_arr, true));
	$task_id = $request_arr['task_id'];
	$status = $request_arr['status'];
	$url1 = $request_arr['data'][0]['message'];
	$sns_id = $request_arr['data'][0]['id'];
	$cur_time = date('YmdHis');
	/*
Array
(
    [social_type] => YOUTUBE
    [data] => Array
        (
            [0] => Array
                (
                    [id] => Ew8XMTjhGbo
                    [message] => http://geminisoft.iptime.org/interface/app/sns/updateStatusSNS_post.php
                )

        )

    [task_id] => 1514
    [message] => 
    [status] => complete
)
	*/

	$task = $db->queryRow("SELECT * FROM BC_TASK WHERE TASK_ID=".$task_id);

	switch($status) {
		case 'complete':
			switch($task['type'])
			{
				case SNS_SHARE:
					$db->exec("
						UPDATE	BC_SOCIAL_TRANSFER
						SET		STATUS='SUCCESS'
								,WEB_URL1='".$url1."'
								,SNS_ID='".$sns_id."'
								,DELETED_DATE=''
						WHERE	TASK_ID=".$task_id
					);
				break;
				case SNS_DELETE:
					$db->exec("
						UPDATE	BC_SOCIAL_TRANSFER
						SET		STATUS='DELETED'
								,WEB_URL1='".$url1."'
								,SNS_ID='".$sns_id."'
								,DELETED_DATE='".$cur_time."'
						WHERE	TASK_ID=".$task_id
					);
				break;
			}
			$db->exec("
				UPDATE	BC_TASK
				SET		STATUS='complete'
						,START_DATETIME=(SELECT CREATION_DATETIME FROM BC_TASK WHERE TASK_ID=".$task_id.")
						,COMPLETE_DATETIME='".$cur_time."'
						,PROGRESS=100
				WHERE	TASK_ID=".$task_id
			);
			
		break;
		case 'false':
			$db->exec("
				UPDATE	BC_TASK
				SET		STATUS='error'
						,PROGRESS=0
				WHERE	TASK_ID=".$task_id
			);
			$db->exec("
				UPDATE	BC_SOCIAL_TRANSFER
				SET		STATUS='FAIL'
				WHERE	TASK_ID=".$task_id
			);
		break;
	}


	echo json_encode(array(
		"success" => "true",
		"message" => "OK"
	));
}
catch(Exception $e)
{
	echo json_encode(array(
		"success" => "false",
		"message" => $e->getMessage()
	));
}
?>