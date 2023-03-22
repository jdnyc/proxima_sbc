<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

try
{
	$insert_task = new TaskManager($db);
	//echo $task_id = $insert_task->start_task_workflow('4482336', 'delete_proxy', 'admin');
}
catch(Exception $e)
{

	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}