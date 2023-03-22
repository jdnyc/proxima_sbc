<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$category_user_id	= $_POST['category_user_id'];
$user_id 			= $_POST['user_id'];
$category_id		= $_POST['category_id'];
$action				= $_POST['action'];

try
{

//	$users = json_decode($_POST['user_id']);
//
//	foreach($users as $user_id)
//	{
//		$dupl_name = $db->queryOne("select m.name name from user_mapping um, member m where m.user_id = um.user_id and um.user_id='$user_id'");
//		if( !empty($dupl_name) )
//		{
//			throw new Exception($dupl_name.'님은 이미 등록된 사용자입니다.', -5);
//		}
//	}
	echo '{"success":true,"msg":"성공"}';

}
catch (Exception $e)
{
	echo '{"success":false,"msg":"'.$e->getMessage().'"}';
}
?>