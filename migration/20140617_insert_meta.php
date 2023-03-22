
<?php
set_time_limit(0);
define('TEMP_ROOT', 'D:/Proxima-Apps/chanps');

$_SERVER['DOCUMENT_ROOT'] = TEMP_ROOT;

require_once(TEMP_ROOT.'/lib/config.php');

$created_time = date('YmdHis');

define('CREATED_TIME', $created_time);

$log_path = TEMP_ROOT.'/log/'.basename(__FILE__).'_'.$cur_date.'.log';
$log_path_error = TEMP_ROOT.'/log/'.basename(__FILE__).'_error_'.$cur_date.'.log';
try
{


	$query = "CREATE TABLE \"BC_USR_META_VALUE_5555\"
(
	\"CONTENT_ID\" NUMBER NOT NULL ENABLE,
	\"F1111\"      VARCHAR2(4000 BYTE),
	\"F2222\"      VARCHAR2(4000 BYTE)
)";

	file_put_contents($log_path, '시작 : '.date("Y-m-d H:i:s")."\n", FILE_APPEND);

	$datas = $db->exec($query);



	file_put_contents($log_path, '종료 : '.date("Y-m-d H:i:s")."\n", FILE_APPEND);
}

catch ( Exception $e )
{
	file_put_contents($log_path_error, date("Y-m-d H:i:s").' '.$e->getMessage().' '.$db->last_query."\n", FILE_APPEND);
	echo $e->getMessage().' '.$db->last_query;
}


?>