<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/searchengine/solr/searcher.class.php');

$content_id = $_REQUEST['content_id'];
$del_continue = $_REQUEST['del_continue'];

try
{
	if($content_id)
	{
//		$delete = $db->exec("delete from delete_content_list where content_id=$content_id");

		$query1 = "update bc_content set is_deleted='N', status='2' where content_id= '$content_id' ";
		$db->exec($query1);

		$query2 = "delete delete_content_list where content_id = '$content_id'";
		$db->exec($query2);

//		$s = new Searcher($db);
//		$s->update($content_id, 'NPS');

		echo json_encode(array(
			'success' => true,
			'msg' => '반려완료'
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