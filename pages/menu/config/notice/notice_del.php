<?php
/*필드
	fields: [
		{name: 'id'},
		{name: 'title'},
		{name: 'created_time', type: 'date', dateFormat: 'YmdHis'}	
		{name: 'content'}
	]
*/
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

//$user_id = $_SESSION['user']['user_id'];

$id = $_POST['id'];
try
{
	$delete = $db->exec("
		delete
		from bc_notice
		where notice_id = '$id'
	");
	
	echo "{success: true,msg:'삭제 성공'}";
}
catch (Exception $e)
{
	echo "{failure: true,msg:'삭제 실패 : ".$e->getMessage()."'}";
}

?>