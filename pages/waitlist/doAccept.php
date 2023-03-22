<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/db.php';
require_once($_SERVER['DOCUMENT_ROOT'].'/searchengine/solr/searcher.class.php');

$user_id		= $_SESSION['user']['user_id'];
try
{
	
//	if ($db->supports('transactions'))
//	{
//		$db->beginTransaction();
//	}

	$content_ids = json_decode( $_POST['content_ids'] , true);		
	if(empty($_POST['content_ids'])) throw new Exception('콘텐츠ID정보가 없습니다.');

	foreach($content_ids as $content_id)
	{	
		$content = queryRow("select * from bc_content where content_id='$content_id'");

		if( !checkAllowGrant($user_id,$content_id,GRANT_ACCEPT ) ) 
		{
			 throw new Exception('퀀한이 없습니다.');
		}
		
		$sql = "update bc_content set status='1' where content_id='$content_id' ";
		$r = queryExec($sql);
		
		$description = '승인';
		insertLog('accept', $content['reg_user_id'], $content_id, $description);

		// 검색엔진에 등록/
//		$s = new Searcher($db);
//		$s->update($content_id, 'DAS');
	}
	
//	$db->commit();

	echo json_encode(array(
		'success' => true
	));
}
catch(Exception $e)
{
//	if ($db->inTransaction())
//	{
//		$db->rollback();
//	}

	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

?>