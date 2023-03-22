
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

	$ud_contents = $db->queryAll("select * from BC_UD_CONTENT order by show_order");

	foreach($ud_contents as $ud_content)
	{
		$ud_content_code = $ud_content['ud_content_code'];
		$ud_content_id = $ud_content['ud_content_id'];

		//메타테이블 생성
		$table_name = strtoupper("BC_USRMETA_".$ud_content_code);
		$upquery = "CREATE TABLE $table_name ( USR_CONTENT_ID NUMBER NOT NULL ENABLE, CONSTRAINT ".$table_name."_PK PRIMARY KEY   (    USR_CONTENT_ID   )  ENABLE )";

		$table_check = $db->queryOne("SELECT count(OBJECT_NAME) FROM USER_OBJECTS WHERE OBJECT_TYPE ='TABLE' and OBJECT_NAME='$table_name'");
		if($table_check == 0 ){
			file_put_contents($log_path, $upquery."\n", FILE_APPEND);
			$r = $db->exec($upquery);
		}


		$bc_usr_meta_fields = $db->queryAll("select * from BC_USR_META_FIELD where ud_content_id=$ud_content_id and USR_META_FIELD_TYPE!='container' order by show_order");

		foreach($bc_usr_meta_fields as $usr_meta_field)
		{
			$usr_meta_field_code = 'USR_'.strtoupper($usr_meta_field['usr_meta_field_code']);

			$upquery = "ALTER TABLE $table_name ADD ($usr_meta_field_code VARCHAR2(4000) )";

			$field_check = $db->queryOne("SELECT count(*) FROM COLS WHERE TABLE_NAME='$table_name' and COLUMN_NAME='$usr_meta_field_code'");
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