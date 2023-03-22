<?php
set_time_limit(0);
define('TEMP_ROOT', '/oradata/web/nps');

$_SERVER['DOCUMENT_ROOT'] = TEMP_ROOT;

require_once(TEMP_ROOT.'/lib/config.php');

$cur_date = date('YmdHis');
$created_time = date('YmdHis');

define('CREATED_TIME', $created_time);

$log_path = TEMP_ROOT.'/log/'.basename(__FILE__).'_'.$cur_date.'.log';
$log_path_error = TEMP_ROOT.'/log/'.basename(__FILE__).'_error_'.$cur_date.'.log';
try
{
	$query = " select * from view_content where category_id='5391516' order by content_id ";

	file_put_contents($log_path, '시작 : '.date("Y-m-d H:i:s")."\n", FILE_APPEND);

	$datas = $db->queryAll($query);

	foreach($datas as $data)
	{
		$content_id = $data['content_id'];
		$category_id = $data['category_id'];

		$new_category_id = 5392068;

		$media= $db->queryRow("select * from bc_media where content_id='$content_id' and media_type='original'");

		$path = $media['path'];

		$path_array = explode('/', $path);


		$new_path = $path_array[0].'/'.'ingest'.'/'.$path_array[1];
		$new_path = $db->escape($new_path);

		echo $new_path.'<br />';

		$u = "update bc_media set path='$new_path' where content_id= '$content_id'  and media_type='original' ";
		//$r = $db->exec($u);
		$u = "update bc_content set category_id='$new_category_id', category_full_path ='/0/5392068' where content_id= '$content_id'";
		//$r = $db->exec($u);

	}

	file_put_contents($log_path, '종료 : '.date("Y-m-d H:i:s")."\n", FILE_APPEND);
}

catch ( Exception $e )
{
	file_put_contents($log_path_error, date("Y-m-d H:i:s").' '.$e->getMessage().' '.$db->last_query."\n", FILE_APPEND);
	echo $e->getMessage().' '.$db->last_query;
}


?>