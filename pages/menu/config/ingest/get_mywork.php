<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
$user_id = $_SESSION['user']['user_id'];
$worker=$db->queryOne("select member_id from member where user_id ='$user_id'");
$id = $_POST['id'];

try
{
	//if(empty($_SESSION['user']['member_id'])) {throw new Exception($error->getMessage());}

	$get_mywork = $db->query(" update ingest_list set worker ='$worker' where id = '$id'");

	echo "{success: true,msg:'작업 성공'}";
}
catch (Exception $e)
{
	echo "{failure: true,msg:'작업 실패 : ".$e->getMessage()."'}";
}

?>