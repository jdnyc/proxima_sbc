<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
try{
	$slide_thumb_value = $_POST['slide_thumb_value'];
	$user_id = $user_id = $_SESSION['user']['user_id'];

	$r = $db->exec("UPDATE bc_member_option SET
				slide_thumbnail_size = $slide_thumb_value
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


