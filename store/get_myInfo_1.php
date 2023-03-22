<?php
session_start();
header("Content-type: application/json; charset=UTF-8");


require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$user_id = trim($_REQUEST['user_id']);
//$password = md5(trim($_REQUEST['password']));

try
{

	$user = $db->queryOne("select user_nm, ori_password from bc_member where user_id='$user_id' ");

//	$result_email = $mdb->queryOne("select email from bc_member where user_id=$user_id");

		echo json_encode(array(
			'success' => true,
			'data' => $user
		));

}
catch (Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

?>
