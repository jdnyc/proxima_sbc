<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
session_start();
try{
	$show_yn = $_POST['show_yn'];
	$user_id = $user_id = $_SESSION['user']['user_id'];

	$r = $db->exec("UPDATE bc_member_option SET
				show_content_subcat_yn = '$show_yn'
				WHERE	MEMBER_ID = (
					SELECT	MEMBER_ID
					FROM		BC_MEMBER
					WHERE	USER_ID =  '".$user_id."'
				)");

	echo json_encode(array(
		'success' => true
	));

}catch(Exception $e){
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}