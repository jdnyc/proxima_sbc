
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
	file_put_contents($log_path, '시작 : '.date("Y-m-d H:i:s")."\n", FILE_APPEND);

	//사용자 정의 콘텐츠 별 / 필드에 대한 메타테이블 추가

	$bs_contents = $db->queryAll("select * from BC_BS_CONTENT order by show_order");

	foreach($bs_contents as $bs_content)
	{
		$bs_content_code = $bs_content['bs_content_code'];
		$bs_content_id = $bs_content['bs_content_id'];

		//메타테이블 생성
		$table_name = strtoupper("BC_SYSMETA_".$bs_content_code);
		$upquery = "CREATE TABLE $table_name ( SYS_CONTENT_ID NUMBER NOT NULL ENABLE, CONSTRAINT ".$table_name."_PK PRIMARY KEY   (    SYS_CONTENT_ID   )  ENABLE )";

		$table_check = $db->queryOne("SELECT count(OBJECT_NAME) FROM USER_OBJECTS WHERE OBJECT_TYPE ='TABLE' and OBJECT_NAME='$table_name'");
		if($table_check == 0 ){
			file_put_contents($log_path, $upquery."\n", FILE_APPEND);
			$r = $db->exec($upquery);
		}


		$bc_sys_meta_fields = $db->queryAll("select * from BC_SYS_META_FIELD where bs_content_id=$bs_content_id and FIELD_INPUT_TYPE!='container' order by show_order");

		foreach($bc_sys_meta_fields as $sys_meta_field)
		{
			$sys_meta_field_code = 'SYS_'.strtoupper($sys_meta_field['sys_meta_field_code']);

			$upquery = "ALTER TABLE $table_name ADD ($sys_meta_field_code VARCHAR2(4000) )";

			$field_check = $db->queryOne("SELECT count(*) FROM COLS WHERE TABLE_NAME='$table_name' and COLUMN_NAME='$sys_meta_field_code'");
			if($field_check == 0 ){
				file_put_contents($log_path, $upquery."\n", FILE_APPEND);
				$r = $db->exec($upquery);
			}
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