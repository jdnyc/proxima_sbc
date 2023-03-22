<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/SGL.class.php');
//require_once($_SERVER['DOCUMENT_ROOT'].'/class/Util.class.php');

$is_debug = 1;

if ( false )
{
	define('TASK_TABLE', 'task_test');
	define('TASK_LOG_TABLE', 'task_log_test');
}
else
{
	define('TASK_TABLE', 'bc_task');
	define('TASK_LOG_TABLE', 'bc_task_log');
}

try
{
	$action = $_POST['action'];
	$reason	= $_POST['reason'];
	$task_id_list = $_POST['task_id_list'];

	$creation_date = date("YmdHis");

	if (empty($action) || empty($task_id_list)) {
        //throw new Exception('작업에 필요한 매개변수가 부족합니다.');
        throw new Exception(_text('MSG02201'));//잘못된 접근입니다. 다시 시도 해 주시기 바랍니다.
	}

	if (empty($reason)) {
        //$reason = '사용자에 의한 작업 '.$action;
        $reason = _text('MN04096').' '.$action;
	}

	// 무조건 배열 처리
	if (!is_array($task_id_list)) {
		$task_id_list[] = $task_id_list;
	}

	foreach ($task_id_list as $task_id) {
		$curr_status = $db->queryOne("SELECT STATUS FROM ".TASK_TABLE." WHERE TASK_ID=".$task_id);
        //if (empty($curr_status)) throw new Exception('작업이 존재하지 않습니다.(task id: '.$task_id.')');
        if (empty($curr_status)) throw new Exception(_text('MSG02201'));//잘못된 접근입니다. 다시 시도 해 주시기 바랍니다.

		if(  strtoupper($action) != 'PRIORITY'  )
		{
			$rtn = $db->exec("UPDATE ".TASK_TABLE." SET STATUS='$action' WHERE TASK_ID=".$task_id);
		}

		if ( strtoupper($action) == 'RETRY') {
			if ( strtoupper($curr_status) == 'PROCESSING' || strtoupper($curr_status) == 'PROGRESSING') {
				$curr_dt = date('YmdHis');
				$last_update_dt = $db->queryOne("select creation_date from ".TASK_LOG_TABLE." where task_id=$task_id order by task_log_id desc");
				if (empty($last_update_dt)) {
					//throw new Exception('작업 로그가 존재하지 않습니다.');
					throw new Exception(_text('MSG02050'));
				}

				$diff_dt = strtotime($curr_dt) - strtotime($last_update_dt);
				if ( $diff_dt <  TASK_TIMEOUT) {
                    //throw new Exception('진행중인 작업은 재시작 될 수 없습니다.');
                    throw new Exception(_text('MSG00116'));//진행중인 작업입니다
				}
			}

			$task = $db->queryRow("select * from ".TASK_TABLE." where task_id=".$task_id);

			$task['target'] = str_replace("'", "\'", $task['target']);
			$task['source'] = str_replace("'", "\'", $task['source']);

            $query = "update ARCHIVE SET REQNUM=null WHERE task_id='{$task['task_id']}'";
            $rtn = $db->exec($query);
			$query = "update ".TASK_TABLE." set status='queue' , ASSIGN_IP='', PROGRESS=null where task_id='{$task['task_id']}'";

			//$new_task_id =  getSequence('TASK_SEQ');

//			$query = "insert into ".TASK_TABLE." (task_id,media_id, type, status, source, source_id, source_pw,
//															target, target_id, target_pw, parameter, priority, destination, creation_datetime, task_workflow_id, job_priority, task_rule_id)
//								values
//							('$new_task_id', '{$task['media_id']}', '{$task['type']}', 'queue', '{$task['source']}', '{$task['source_id']}', '{$task['source_pw']}',
//							'{$task['target']}', '{$task['target_id']}', '{$task['target_pw']}', '{$task['parameter']}', '{$task['priority']}',
//							'{$task['destination']}', '".date('YmdHis')."', '{$task['task_workflow_id']}', '{$task['job_priority']}', '{$task['task_rule_id']}')";
			$rtn = $db->exec($query);
			$rtn = $db->exec("INSERT INTO ".TASK_LOG_TABLE." (TASK_ID, DESCRIPTION, CREATION_DATE)
								VALUES ('$task_id', '$reason' ,'$creation_date' )");
			//$rtn = $db->exec("DELETE FROM ".TASK_TABLE." WHERE TASK_ID=$task_id ");
		}
		else if ( strtoupper($action) == 'CANCEL' ) {
			$task_info = $db->queryRow("
							SELECT *
							FROM BC_TASK
							WHERE TASK_ID = '$task_id'
						");
			// 아카이브, 리스토어, 아카이브 삭제에 대해서 취소 명령일 경우 처리
			if(in_array($task_info['type'], array(ARCHIVE, RESTORE, ARCHIVE_DELETE))) {
				//stop job은 request_id를 넘겨야 됨. request_id는 sgl_archve 테이블에서 조회
				$sgl_request_id = $db->queryOne("
									SELECT SESSION_ID
									FROM SGL_ARCHIVE
									WHERE TASK_ID = '$task_id'
								");
				$sgl = new SGL();
				$return = $sgl->FlashNetStopJob($sgl_request_id);
				if(!$return['success']) {
					throw new Exception ($return['msg']);
				}
			}

			$rtn = $db->exec("update ".TASK_TABLE." set status='cancel' where task_id=$task_id ");
			

			$rtn = $db->exec("insert into ".TASK_LOG_TABLE." (TASK_ID,DESCRIPTION,CREATION_DATE) values('$task_id','$reason' ,'$creation_date' )");

		}
		else if( strtoupper($action) == 'DELETE' ) {
			$rtn = $db->exec("update ".TASK_TABLE." set status='delete' where task_id=$task_id ");
		

			$rtn = $db->exec("insert into ".TASK_LOG_TABLE." (TASK_ID,DESCRIPTION,CREATION_DATE) values('$task_id','$reason' ,'$creation_date' )");
		}
		else if(  strtoupper($action) == 'PRIORITY'  )
		{
			$rtn = $db->exec("update ".TASK_TABLE." set PRIORITY='200' where task_id=$task_id ");
		}
	}

	die(json_encode(array(
		'success' => true
	)));
}
catch (Exception $e)
{
	switch($e->getCode())
	{
		case ERROR_QUERY:
			$msg = $e->getMessage().'( '.$db->last_query . ' )';
		break;

		default:
			$msg = $e->getMessage();
		break;
	}

	die(json_encode(array(
		'success' => false,
		'msg' => $msg
	)));
}
?>