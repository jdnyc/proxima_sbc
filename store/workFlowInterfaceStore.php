<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

try
{
	$root_task = $_REQUEST['root_task'];
	$workflow_id = $_REQUEST['workflow_id'];
	$content_id = $_REQUEST['content_id'];

	if( !empty($root_task) ) {
		$root_task_query = "ROOT_TASK = $root_task ";
	}

	$query_all = "
		SELECT B.JOB_NAME
		      ,A.TASK_ID
		      ,A.CREATION_DATETIME
		      ,A.START_DATETIME
		      ,A.COMPLETE_DATETIME
		      ,A.PARAMETER
		      ,A.PROGRESS
		      --,A.STATUS
			  ,CASE A.STATUS
					WHEN 'complete' THEN '"._text('MN00011')."'--성공
					WHEN 'queue' THEN '"._text('MN00039')."'--대기
					WHEN 'processing' THEN '"._text('MN00262')."'--처리중
					WHEN 'error' THEN '"._text('MN00012')."'--실패
					ELSE A.STATUS
			  END AS STATUS

		      ,A.SOURCE
		      ,A.TARGET
		FROM  (      
		      SELECT *
		      FROM  BC_TASK
		      WHERE ".$root_task_query."
		      ) A
		      LEFT OUTER JOIN
		      (
		      SELECT A.WORKFLOW_RULE_ID
		            ,C.JOB_NAME
		      FROM  BC_TASK_WORKFLOW_RULE A
		            LEFT OUTER JOIN 
		            BC_TASK_WORKFLOW B
		            ON (A.TASK_WORKFLOW_ID = B.TASK_WORKFLOW_ID)
		            LEFT OUTER JOIN
		            BC_TASK_RULE C
		            ON (A.TASK_RULE_ID = c.TASK_RULE_ID)
		      ) B
		      ON (A.WORKFLOW_RULE_ID = B.WORKFLOW_RULE_ID)
		ORDER BY A.TASK_ID
			  ";
	$result = $db->queryAll($query_all);

	echo json_encode(array(
		'success' => true,
		'status' =>  0,
		'message' => "OK",
		'data' => $result
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
