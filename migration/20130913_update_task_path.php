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
	$query = " select * from bc_task where type='31' and status='error' and creation_datetime >=20130902135349 ";

	file_put_contents($log_path, '시작 : '.date("Y-m-d H:i:s")."\n", FILE_APPEND);

	$datas = $db->queryAll($query);

	foreach($datas as $data)
	{
		$media_id = $data['media_id'];
		$task_id = $data['task_id'];
		$media_id = $data['media_id'];

		$ori_media = $db->queryRow("select * from bc_media where content_Id=(select content_id from bc_media where media_id='$media_id') and media_type='original'");

		if( empty($ori_media) || $ori_media['ststus'] == 1 ) continue;

		$new_path = $ori_media['path'];



		$query = "update bc_task set status='queue' , source='$new_path' where task_id='$task_id' ";
		file_put_contents($log_path, $query."\n", FILE_APPEND);

		//$r = $db->exec($query);
	}

	file_put_contents($log_path, '종료 : '.date("Y-m-d H:i:s")."\n", FILE_APPEND);
}

catch ( Exception $e )
{
	file_put_contents($log_path_error, date("Y-m-d H:i:s").' '.$e->getMessage().' '.$db->last_query."\n", FILE_APPEND);
	echo $e->getMessage().' '.$db->last_query;
}


?>