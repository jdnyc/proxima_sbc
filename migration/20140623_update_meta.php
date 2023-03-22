
<?php
set_time_limit(0);
define('TEMP_ROOT', 'D:/Proxima-Apps_NPS/chanps');

$_SERVER['DOCUMENT_ROOT'] = TEMP_ROOT;

require_once(TEMP_ROOT.'/lib/config.php');

$created_time = date('YmdHis');

define('CREATED_TIME', $created_time);

$log_path = TEMP_ROOT.'/log/'.basename(__FILE__).'_'.$cur_date.'.log';
$log_path_error = TEMP_ROOT.'/log/'.basename(__FILE__).'_error_'.$cur_date.'.log';
try
{
	file_put_contents($log_path, '시작 : '.date("Y-m-d H:i:s")."\n", FILE_APPEND);

	//사용자 정의 콘텐츠 별 / 필드에 대한 메타테이블 추가

	$ud_contents = $db->queryAll("select * from BC_UD_CONTENT order by show_order");

	foreach($ud_contents as $ud_content)
	{
		$ud_content_code = $ud_content['ud_content_code'];
		$ud_content_id = $ud_content['ud_content_id'];

		//메타테이블 생성
		$table_name = strtoupper("BC_USR_META_VALUE_".$ud_content_code);
		$upquery = "ALTER TABLE $table_name  RENAME COLUMN CONTENT_ID TO USR_CONTENT_ID ";
		$r = $db->exec($upquery);



		$bc_usr_meta_fields = $db->queryAll("select * from BC_USR_META_FIELD where ud_content_id=$ud_content_id and USR_META_FIELD_TYPE!='container' order by show_order");

		foreach($bc_usr_meta_fields as $usr_meta_field)
		{
			$usr_meta_field_code = strtoupper($usr_meta_field['usr_meta_field_code']);

			$upquery = "ALTER TABLE $table_name RENAME COLUMN $usr_meta_field_code TO USR_$usr_meta_field_code  ";
			$r = $db->exec($upquery);
		}
	}


	$bs_contents = $db->queryAll("select * from BC_BS_CONTENT order by show_order");

	foreach($bs_contents as $bs_content)
	{
		$bs_content_code = $bs_content['bs_content_code'];
		$bs_content_id = $bs_content['bs_content_id'];

		//메타테이블 생성
		$table_name = strtoupper("BC_SYS_META_VALUE_".$bs_content_code);
		$upquery = "ALTER TABLE $table_name  RENAME COLUMN CONTENT_ID TO SYS_CONTENT_ID ";
		$r = $db->exec($upquery);



		$bc_sys_meta_fields = $db->queryAll("select * from BC_SYS_META_FIELD where bs_content_id=$bs_content_id and FIELD_INPUT_TYPE!='container' order by show_order");

		foreach($bc_sys_meta_fields as $sys_meta_field)
		{
			$sys_meta_field_code = strtoupper($sys_meta_field['sys_meta_field_code']);

			$upquery = "ALTER TABLE $table_name RENAME COLUMN $sys_meta_field_code TO SYS_$sys_meta_field_code  ";
			$r = $db->exec($upquery);
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