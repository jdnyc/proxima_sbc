<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');


$del_list = json_decode( $_POST['id'] );

try
{
	foreach($del_list as $list)
	{
		$id = $list->id;

		//////////인제스트요청 콘텐츠 삭제//////////디비 제약조건으로 ingest_metadata,ingest_tc_list , ingest_meta_multi 까지 삭제
		$delete = $db->exec("delete from ingest where id = '$id'");
	}

	echo "{success: true, msg:'삭제 성공'}";
}
catch (Exception $e)
{
	echo "{failure: true, msg:'삭제 실패 : ".$e->getMessage()."'}";
}

?>