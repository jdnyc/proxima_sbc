<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/db.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/searchengine/solr/searcher.class.php');

$created_time =date('YmdHis');
$channel = 'das_web';
$user_id = $_SESSION['user']['user_id'];

try
{
	$items = json_decode(urldecode($_POST['values']));

	$contents_id		= $items->k_contents->contents_id;
 	$meta_tables_id		= $items->k_contents->meta_tables_id;
	$fields				= $items->values;


	executeQuery(sprintf("update content set status='%s' where content_id in (%s)",
									CONTENT_STATUS_COMPLETE,
									$contents_id));

	$contents=explode(',', $contents_id);
	$total = count($contents);

	for($i=0;$i<$total;$i++)
	{
		$content_id=$contents[$i];

		//승인시 메타데이터 입수일자 업데이트 처리 by 이성용

		$content_data = $db->queryRow("select content_type_id,  meta_table_id from content where content_id='$content_id'");

		$meta_table_id = $content_data['meta_table_id'];
		$content_type_id = $content_data['content_type_id'];

		$is_nps = $db->queryOne("select nps_content_id from content where content_id='$content_id'");

		if( $meta_table_id == PRE_PRODUCE )
		{
			$meta_field_id ='4002641'; //tv방송프로그램 입수일자 메타필드
			$accept_update_meta_value = $db->exec("update meta_value set value='$created_time' where content_id='$content_id' and meta_field_id='$meta_field_id'");
		}
		else if( $meta_table_id == CLEAN )
		{

			if( !empty( $is_nps ) ) //NPS에서 전송되어 온 것이 소재영상일경우에만 업데이트
			{
				$meta_field_id ='4002671'; //소재영상 입수일자 메타필드
				$accept_update_meta_value = $db->exec("update meta_value set value='$created_time' where content_id='$content_id' and meta_field_id='$meta_field_id'");
			}
		}

		///////////////검색 엔진 등록//////////
//		$s = new Searcher($db);
//		$s->add($content_id, 'DAS');

		//로그 남기기 2011-1-28 by 이성용

		$description = '승인';
		$log_id = getNextSequence();
		executeQuery("insert into log (id, action, user_id, link_table, link_table_id, created_time, link_table_meta, description) values ($log_id, 'accept', '$user_id', '$content_type_id', '$content_id', '$created_time', '$meta_table_id', '$description')");
	}

	if( !empty( $is_nps ) ) //NPS에서 전송되어 온 것일때 승인시 니어라인 아카이브 등록
	{

		$channel = 'das_web';
		$source = $db->queryOne("select path from media where content_id='$content_id' and type = 'original'");

		insert_task_query($content_id, $source, $source, $created_time, $channel);

	}


	//2011.01.09 김성민 :: das의 등록대기영상에서 승인시 추가작업되는 항목이 없어서 작업등록코드는 주석처리.
	/*
	for($i=0;$i<$total;$i++)//task에 등록
	{
		$content_id=$contents[$i];
		$source = $db->queryOne("select path from media where content_id='$content_id' and type = 'original'");
		insert_task_query($content_id, $source, $source, $created_time, $channel);
	}
	*/
	die(json_encode(array(
		'success' => true
	)));
}
catch (Exception $e)
{
	die(json_encode(array(
		'success' => false,
		'msg' => $e->getMessage(),
		'query' => $db->last_query
	)));
}
?>