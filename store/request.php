<?php
session_start();
//require_once ($_SERVER['DOCUMENT_ROOT'] . '/lib/nusoap/nusoap.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

$type = $_GET['type'];
$data = json_decode(file_get_contents('php://input'));
$user_id = $_SESSION['user']['user_id'];

if (!(@include('request/'.$type.'.php'))) {
	echo json_encode(array(
		'success' => false,
		'msg' => '등록되지 않은 작업 요청 입니다('.$type.').'
	));
}

?>