<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

try {	
	$workflows = $db->queryAll("
					SELECT	USER_TASK_NAME, REGISTER
					FROM	BC_TASK_WORKFLOW
					WHERE	ACTIVITY = '1'
					AND		TYPE != 'p'
					ORDER BY USER_TASK_NAME
				");
	
	// result array 초기화
	$result = array();

	// 결과값에 전체항목을 넣기 위해서 처리
	array_push($result, array(
		'name'	=> _text('MN00008'),//'전체',
		'value'	=> 'all'
	));

	foreach ($workflows as $workflow) {
		array_push($result, array(
			'name'	=> $workflow['user_task_name'],
			'value'	=> $workflow['register']
		));		
	}

	echo json_encode(array(
		'success' => true,
		'data' => $result	
	));		

} catch(Exception $e) {
	echo json_encode(array(
		'success' => false,
		'message' => $e->getMessage(),
		'query' => $db->last_query
	));
}	

?>
