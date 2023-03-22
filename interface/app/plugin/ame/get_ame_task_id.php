<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/db.php';

try {

	$task_id = getSequence('TASK_SEQ');
	if($task_id)
	{
		echo json_encode(array(
			'success' => true,
			'msg' => '성공',
			'task_id'=>$task_id
		));
	}
	else 
	{	
		echo json_encode(array(
			'success' => false,
			'msg' => '작업ID 실패'
		));
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
