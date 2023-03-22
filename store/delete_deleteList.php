<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/searchengine/solr/searcher.class.php');

$content_id = $_REQUEST['content_id'];
$del_continue = $_REQUEST['del_continue'];

try
{
	if($content_id)
	{
//		$delete = $db->exec("delete from delete_content_list where content_id=$content_id");

		$query = "update bc_content set is_deleted='Y', status='0' where content_id= '$content_id' ";
		$db->exec($query);

//		$s = new Searcher($db);
//		$s->delete($content_id);

		echo json_encode(array(
			'success' => true,
			'msg' => '삭제완료'
		));
	}else{
		echo json_encode(array(
			'success' => false,
			'msg' => '정보가 제대로 전달되지 않았습니다.'
		));
	}
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => "에러: $e"
	));
}


?>