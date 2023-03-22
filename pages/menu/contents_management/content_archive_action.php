<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

$action = $_POST['action'];
$user_id = $_SESSION['user']['user_id'];

switch($action){
	case 'approve':
		archive_approve_exec($user_id);
	break;

	case 'cancel':
		archive_cancel_exec();
	break;

	default :
		$msg = '조건이 맞지 않습니다';

		echo json_encode(
			array(
				'success'	=> false,
				'msg' => $msg
		));
	break;
}

function archive_cancel_exec(){
	global $db;
	$data_ids = json_decode($_POST['ids']);

	echo json_encode(
			array(
				'success' => true,
			 		'msg' => '성공적으로  적용되었습니다'
			 	)
		);
}

function archive_approve_exec($user_id){
	global $db;

	$datas = json_decode($_POST['ids']);
	echo json_encode(
		array(
			'success' => true,
				'msg' => '성공적으로  적용되었습니다'
			)
	);

}

function error($msg)
{
	
}

?>
