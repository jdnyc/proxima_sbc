<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
//require_once($_SERVER['DOCUMENT_ROOT'].'/class/Util.class.php');

$is_debug = 1;

try {
	$query = array(
		'select' => array(
			'task_status' => 'SELECT STATUS FROM BC_TASK WHERE TASK_ID = :task_id'
		),
		'insert' => array(
			'task' => "INSERT INTO BC_TASK (MEDIA_ID, TYPE, STATUS,
							SOURCE, SOURCE_ID, SOURCE_PW, TARGET, TARGET_ID, TARGET_PW,
							PARAMETER, PRIORITY, DESTINATION, TASK_WORKFLOW_ID, JOB_PRIORITY, TASK_RULE_ID,
							CREATION_DATETIME)
						VALUES
							(:media_id, :type, :status,
							:source, :source_id, :source_pw, :target, :target_id, :target_pw,
							:parameter, :priority, :destination, :task_workflow_id, :job_priority, :task_rule_id,
							TO_CHAR(CURRENT_DATE, 'YYYYMMDDHHIISS'))",
			'task_log' => 'INSERT INTO BC_TASK_LOG (TASK_ID,DESCRIPTION,CREATION_DATE)
							VALUES(:task_id,:reason, :creation_date)'
		),
		'delete' => array(
			'task' => 'DELETE FROM BC_TASK WHERE TASK_ID = :task_id'
		)
	);

	$action = $_POST['action'];
	$task_id = $_POST['task_id'];

	$reason = '사용자에 의한 작업 '.$action.' 요청';
	$creation_date = date("YmdHis");

	if (!empty($_POST['reason'])) {
		$reason .= ' : '.$db->escape($_POST['reason']);
	}

	if (empty($action) || empty($task_id)) {
		throw new Exception('작업에 필요한 매개변수가 부족합니다.');
	}

	$curr_status = $db->queryOne(str_replace(':task_id', $task_id, $query['select']['task_status']));
	if (empty($curr_status)) throw new Exception('작업이 존재하지 않습니다.(task id: '.$task_id.')');

	$db->parse("UPDATE BC_TASK SET STATUS='$action' WHERE TASK_ID=".$task_id);

	if ( strtoupper($action) == 'RETRY') {
		if ( strtoupper($curr_status) == 'PROCESSING' || strtoupper($curr_status) == 'PROGRESSING') {
			$curr_dt = date('YmdHis');
			$last_update_dt = $db->queryOne("SELECT CREATION_DATE FROM BC_TASK_LOG WHERE TASK_ID=$task_id ORDER BY TASK_LOG_ID DESC");
			if (empty($last_update_dt)) throw new Exception('작업 로그가 존재하지 않습니다.');

			$diff_dt = strtotime($curr_dt) - strtotime($last_update_dt);
			if ($diff_dt < TASK_TIMEOUT) {
				throw new Exception('진행중인 작업은 재시작 될 수 없습니다.');
			}
		}

		$task = $db->queryRow("SELECT * FROM BC_TASK WHERE TASK_ID=".$task_id);

		$task['target'] = str_replace("'", "\'", $task['target']);
		$task['source'] = str_replace("'", "\'", $task['source']);

		// task에 작업흐름 필드 추가를 위해 쿼리수정. 2011/2/13 김성민
		$db->parse($insertTaskQuery);
			$db->bind_by_name(':media_id',			$task['media_id']);
			$db->bind_by_name(':type',				$task['type']);
			$db->bind_by_name(':status',			'queue');
			$db->bind_by_name(':source',			$task['source']);
			$db->bind_by_name(':source_id',			$task['source_id']);
			$db->bind_by_name(':source_pw',			$task['source_pw']);
			$db->bind_by_name(':target',			$task['target']);
			$db->bind_by_name(':target_id',			$task['target_id']);
			$db->bind_by_name(':target_pw',			$task['target_pw']);
			$db->bind_by_name(':parameter',			$task['parameter']);
			$db->bind_by_name(':priority',			$task['priority']);
			$db->bind_by_name(':destination',		$task['destination']);
			$db->bind_by_name(':task_workflow_id',	$task['task_workflow_id']);
			$db->bind_by_name(':job_priority',		$task['job_priority']);
			$db->bind_by_name(':task_rule_id',		$task['task_rule_id']);

		$rtn = $db->exec();
		$rtn = $db->exec($queryDeleteTask);

	}
	else if ( strtoupper($action) == 'CANCEL' )	{
		$rtn = $db->exec("update bc_task set status='cancel', assign_ip='' where task_id=$task_id ");
		$rtn = $db->exec("insert into bc_task_log (TASK_ID,DESCRIPTION,CREATION_DATE) values('$task_id','$reason' ,'$creation_date' )");
	}
	else if( strtoupper($action) == 'DELETE' ) {
		$rtn = $db->exec("update bc_task set status='delete', assign_ip='' where task_id=$task_id ");
		$rtn = $db->exec("insert into bc_task_log (TASK_ID,DESCRIPTION,CREATION_DATE) values('$task_id','$reason' ,'$creation_date' )");
	}

	die(json_encode(array(
		'success' => true
	)));
}
catch (Exception $e) {
	$msg = $e->getMessage();

	die(json_encode(array(
		'success' => false,
		'msg' => $msg
	)));
}
?>