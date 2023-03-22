<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');


$id = $_POST['id'];
$leaf = $_POST['leaf'];
$list = $_POST['list'];
try
{
	if($list =='list')
	{
		//////////인제스트요청 콘텐츠 삭제//////////
		$delete = $db->query("delete from ingest where id = '$id'");
		////////////메타값 삭제///////////////
		$meta_del=$db->exec("delete from ingest_metadata where ingest_id='$id'");
		/////////////타임코드사용시 타임코드 삭제///////////
		//$use_tc = $db->exec("select value from meta_value where content_id='$id' and meta_field_id='".$map_used_tc['used_tc']."'");


		$delete_tc = $db->exec("
			delete
			from ingest_tc_list
			where ingest_list_id ='$id'
			");
	}
	else
	{
		$delete_tc = $db->query("
			delete
			from ingest_tc_list
			where id ='$id'
			");
	}


	echo "{success: true,msg:'삭제 성공'}";
}
catch (Exception $e)
{
	echo "{failure: true,msg:'삭제 실패 : ".$e->getMessage()."'}";
}

?>