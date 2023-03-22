<?php
$_SERVER['DOCUMENT_ROOT'] = empty($_SERVER['DOCUMENT_ROOT']) ?  $_GET['DOCUMENT_ROOT']: $_SERVER['DOCUMENT_ROOT'];
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/searchengine/solr/searcher.class.php');
set_time_limit(0);
$GLOBALS['flag'] = '0';
$cur_date = date('YmdHis');
$created_time = date('YmdHis');

$GLOBALS['log_path'] = $_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.$cur_date.'.log';
try
{

	$query = "select * from bc_content c where c.is_deleted='N' and c.is_group != 'C' ";
	$order = " order by c.content_id desc ";

	//전체 로우
	$total = $db->queryOne("select count(*) from ( $query  ) cnt ");

	$limit = 1000;
	$j = 0;

	file_put_contents($log_path, 'Start : '.date("Y-m-d H:i:s")." : $total \n", FILE_APPEND);

	//전체 for문
	//병렬로 돌릴려면
	//스타트 값을 변경
	for($start = 0 ; $start < $total ; $start += $limit )
	{
		//1000개씩 분할
		$db->setLimit($limit , $start);
		$lists = $db->queryAll($query.$order);

		foreach( $lists as $list )
		{
			echo $total."/".$j++."\r";//확인

			echo $content_id = $list['content_id'];
			//print_r($list);
			@file_put_contents($log_path, 'Check : '.date("Y-m-d H:i:s")." : $content_id \n", FILE_APPEND);
            searchUpdate($content_id);
		}
	}

	file_put_contents($log_path, 'End : '.date("Y-m-d H:i:s")." : $j \n", FILE_APPEND);
}
catch ( Exception $e )
{
	global $log_path;
	file_put_contents($log_path, date("Y-m-d H:i:s").' '.$e->getMessage().' '.$db->last_query."\n", FILE_APPEND);
	echo $e->getMessage().' '.$db->last_query;
}
?>