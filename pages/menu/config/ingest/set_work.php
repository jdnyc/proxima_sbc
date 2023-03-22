<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$user_id = $_POST['user_id'];
$id = $_POST['id'];
try
{
	$member_id = $db->queryOne("select member_id from member where user_id ='$user_id'");
	$get_mywork = $db->query(" update ingest_list set worker ='$member_id' where id = '$id'");

	echo "{success: true,msg:'작업 성공'}";
}
catch (Exception $e)
{
	echo "{failure: true,msg:'작업 실패 : ".$e->getMessage()."'}";
}

?>