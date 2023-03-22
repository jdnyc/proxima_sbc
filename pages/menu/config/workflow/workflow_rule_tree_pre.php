<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
fn_checkAuthPermission($_SESSION);
$user_id = $_SESSION['user']['user_id'];

$task_workflow_id = $_REQUEST['task_workflow_id'];

try
{
	$list = array();
	$root = array(
		'task_rule_id' => '0',
		'icon' => '/led-icons/application_put.png',
		'expanded' => true,
		'job_no' => '0',
		'job_name' => 'Workflow Start',
		'id' => '0',
		'task_workflow_id' => $task_workflow_id,
		'leaf' => false
	);
	$root['children'] =  get_full_node( $task_workflow_id, '0' );
	$list [] = $root ;
	echo json_encode($list);
} catch (Exception $e) {
	echo _text('MN00022').' : '.$e->getMessage();
}

function escape($v) {
	$v = str_replace("'", "\'", $v);
	$v = str_replace("\r", '', $v);
	$v = str_replace("\n", '\\n', $v);

	return $v;
}

function get_full_node($task_workflow_id, $id) {
	global $db;

	$list = array();
	
	if(empty($task_workflow_id)){
		$task_workflow_id = 'null';
	}

	if ($id == 0) {
		$_where = " AND TASK_RULE_PARANT_ID = $id";
	} else {
		$_where = " AND TASK_RULE_PARANT_ID = $id";
	}

	$query = "
		SELECT	TWR.*,
				(SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = TWR.SOURCE_PATH_ID) SRC_PATH,
				(SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = TWR.TARGET_PATH_ID) TAR_PATH
		FROM	BC_TASK_WORKFLOW_RULE TWR
		WHERE	TASK_WORKFLOW_ID = {$task_workflow_id}
		".$_where."
		ORDER BY JOB_PRIORITY ASC
	";
	$datas = $db->queryAll($query);
	foreach ($datas as $data) {
		$node = array();
		//About storage info showing, not use BC_TASK_RULE info. Have to show BC_TASK_WORKFLOW_RULE info.
//		$q = $db->queryRow("
//				SELECT	TR.JOB_NAME, TR.PARAMETER, SOURCE_PATH SOURCE_STORAGE_ID, TARGET_PATH TARGET_STORAGE_ID,
//						(SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = TR.SOURCE_PATH) SRC_PATH,
//						(SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = TR.TARGET_PATH) TAR_PATH
//				FROM	BC_TASK_RULE TR
//				WHERE	TASK_RULE_ID = {$data['task_rule_id']}
//			");
		$q = $db->queryRow("
				SELECT	TR.JOB_NAME, TR.PARAMETER, SOURCE_PATH SOURCE_STORAGE_ID, TARGET_PATH TARGET_STORAGE_ID
				FROM	BC_TASK_RULE TR
				WHERE	TASK_RULE_ID = {$data['task_rule_id']}
			");

		$node['job_name'] = $q['job_name'];
		$node['parameter'] = $q['parameter'];
		//About storage info showing, not use BC_TASK_RULE info. Have to show BC_TASK_WORKFLOW_RULE info.
//		$node['src_path'] = $q['src_path'];
//		$node['tar_path'] = $q['tar_path'];
//		$node['source_storage_id'] = $q['source_storage_id'];
//		$node['target_storage_id'] = $q['target_storage_id'];
		$node['src_path'] = $data['src_path'];
		$node['tar_path'] = $data['tar_path'];
		$node['source_storage_id'] = $data['source_path_id'];
		$node['target_storage_id'] = $data['target_path_id'];
		$node['job_no'] = (string)$data['job_priority'];
		$node['task_workflow_id']= $data['task_workflow_id'];
		$node['workflow_rule_id']= $data['workflow_rule_id'];
		$node['task_rule_id']= $data['task_rule_id'];
		$node['task_rule_parant_id']= $data['task_rule_parant_id'];
		$node['condition']= $data['condition'];
		$node['content_status']= $data['content_status'];
		$node['icon'] = '/led-icons/application_view_detail.png';

		$status_list = getCodeInfo('CONTENT_STATUS');

		foreach ($status_list as $lists) {
			if ($data['content_status'] == $lists['code']) {
				$node['content_status_nm'] = $lists['name'];
			}
		}

		$is_children = $db->queryOne("
							SELECT	COUNT(*)
							FROM	BC_TASK_WORKFLOW_RULE
							WHERE	TASK_WORKFLOW_ID = $task_workflow_id
							AND		TASK_RULE_PARANT_ID = {$node['task_rule_id']}
						");

		if ($is_children > 0) {
			$node['leaf'] = false;
			$node['expanded'] = true;
			$node['children'] = get_full_node($task_workflow_id, $data['task_rule_id']);
		} else {
			$node['leaf'] = false;
			$node['expanded'] = false;
			$node['children'] = array();
		}

		$list [] = $node;
	}

	return $list ;
}

?>
