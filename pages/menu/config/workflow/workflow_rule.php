<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$task_workflow_id = $_REQUEST['task_workflow_id'];

$task_rule = $db->queryAll("SELECT * FROM BC_TASK_WORKFLOW_RULE WHERE TASK_WORKFLOW_ID = $task_workflow_id ORDER BY JOB_PRIORITY ASC");

//print_r($task_rule);


$data = array();
$i= 0;
foreach($task_rule as $lists)
{
	$q = $db->queryRow("
			SELECT	TR.JOB_NAME, TR.PARAMETER,
					(SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = TR.SOURCE_PATH) SRC_PATH, 
					(SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = TR.TARGET_PATH) TAR_PATH
			FROM	BC_TASK_RULE TR
			WHERE	TASK_RULE_ID = {$lists['task_rule_id']}
		");
	
	array_push($data, $q);
	$data[$i]['job_no']= $lists['job_priority'];
	$data[$i]['task_workflow_id']= $lists['task_workflow_id'];
	$data[$i]['workflow_rule_id']= $lists['workflow_rule_id'];
	$data[$i]['task_rule_id']= $lists['task_rule_id'];
	$i++;
}

echo json_encode(array(
	'success' => true,
	'data' => $data
));

?>