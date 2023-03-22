<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

try
{
	$social_type = $_POST['social_type_id'];
	$sns_user_id = $_POST['user_id'];
	$token = $_POST['token'];
	$use_yn = $_POST['use_yn'];
	$password = $_POST['password'];
	if($use_yn == 'true') {
		$use_yn = 'Y';
	} else {
		$use_yn = 'N';
	}

	if($social_type == 'FACEBOOK') {
		if(!strstr($token, 'access_token=')) {
			$token = 'access_token='.$token;
		}
		$db->exec("
			UPDATE	BC_CODE
			SET		REF1='".$sns_user_id."'
					,REF2='".$password."'
					,REF3='".$token."'
					,USE_YN='".$use_yn."'
			WHERE	CODE='".$social_type."'
		");
	} else {
		$db->exec("
			UPDATE	BC_CODE
			SET		REF1='".$sns_user_id."'
					,REF2='".$password."'
					,USE_YN='".$use_yn."'
			WHERE	CODE='".$social_type."'
		");
	}

	echo json_encode(array(
		'success' => true,
		'data' => $data
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