<?php
set_time_limit(0);
define('TEMP_ROOT', '/oradata/web/nps');
require_once(TEMP_ROOT.'/lib/config.php');

$cur_date = date('YmdHis');
$created_time = date('YmdHis');

define('CREATED_TIME', $created_time);

$log_path = TEMP_ROOT.'/log/'.basename(__FILE__).'_'.$cur_date.'.log';
$log_path_error = TEMP_ROOT.'/log/'.basename(__FILE__).'_error_'.$cur_date.'.log';
try
{
	$query = " select * from view_content where sub_category_id is not null  ";
	$order = "  order by content_id  "; 
	
	file_put_contents($log_path, '시작 : '.date("Y-m-d H:i:s")."\n", FILE_APPEND);

	$limit = 1000;
	$j = 1;

	$total = $db->queryOne("select count(*) from ( $query ) cnt ");

	for( $start = 0 ; $start < $total ; $start += $limit )
	{
		//1000개씩 분할
		$db->setLimit($limit , $start);
		$lists = $db->queryAll($query.$order);

		foreach( $lists as $list )
		{
			$content_id = $list['content_id'];
			$category_id = $list['category_id'];
			$category_full_path_array = explode('/',$list['category_full_path']);
			array_pop($category_full_path_array);
			$prog_category_id = array_pop($category_full_path_array);
			$upquery = "update bc_content set category_id='$prog_category_id' , category_full_path='/0/$prog_category_id' where content_id='$content_id' ";
			file_put_contents($log_path, $upquery."\n", FILE_APPEND);

			//$r = $db->exec($upquery);

			$upquery = "update nps_work_list set category_id='$prog_category_id' where content_id='$content_id'";
			file_put_contents($log_path, $upquery."\n", FILE_APPEND);
			
			//$r = $db->exec($upquery);
		}
	}



	file_put_contents($log_path, '종료 : '.date("Y-m-d H:i:s")."\n", FILE_APPEND);
}

catch ( Exception $e )
{
	file_put_contents($log_path_error, date("Y-m-d H:i:s").' '.$e->getMessage().' '.$db->last_query."\n", FILE_APPEND);
	echo $e->getMessage().' '.$db->last_query;	
}


?>