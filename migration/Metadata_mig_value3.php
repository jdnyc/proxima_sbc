
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

	//기존 밸류 마이그레이션
	$limit = 1000;
	$start = 0;

	$total = $db->queryOne("select count(content_id) from bc_content ");

	for($start=0; $start < $total ; $start += $limit)
	{
		$db->setLimit($limit , $start);
		$contents = $db->queryAll("select bsc.bs_content_title,bsc.bs_content_code, udc.ud_content_title, udc.ud_content_code,  c.* from bc_content c,BC_UD_CONTENT udc,BC_BS_CONTENT bsc where bsc.bs_content_id=c.bs_content_id and c.ud_content_id=udc.ud_content_id order by content_id");

		foreach($contents as $content)
		{
			$is_usr_value_field = false;
			$is_sys_value_field = false;
			$content_id = $content['content_id'];
			$bs_content_code = $content['bs_content_code'];//시스템 테이블
			$bs_content_id = $content['bs_content_id'];//
			$ud_content_id = $content['ud_content_id'];//
			$ud_content_code = $content['ud_content_code'];//사용자정의 테이블

			$sys_field_info = MetaDataClass::getMetaFieldInfo('sys' , $bs_content_id );
			$sys_id_to_name_map = array();
			$sys_id_to_val_map = array();
			foreach($sys_field_info as $sys_field){
				if( $sys_field[field_input_type] == 'container' ) continue;
				$sys_id_to_name_map[$sys_field[sys_meta_field_id]] = 'SYS_'.$sys_field[sys_meta_field_code];
			}

			$sys_values = $db->queryAll("select * from BC_SYS_META_VALUE where content_id= $content_id ");
			foreach($sys_values as $sys_value){
				$sys_id_to_val_map[$sys_value[sys_meta_field_id]] = $sys_value[sys_meta_value];
			}


			$usr_field_info = MetaDataClass::getMetaFieldInfo('usr' , $ud_content_id );
			$usr_id_to_name_map = array();
			$usr_id_to_val_map = array();
			foreach($usr_field_info as $usr_field){
				if( $usr_field[usr_meta_field_type] == 'container' ) continue;
				$usr_id_to_name_map[$usr_field[usr_meta_field_id]] = 'USR_'.$usr_field[usr_meta_field_code];
			}

			$usr_values = $db->queryAll("select * from BC_USR_META_VALUE where content_id= $content_id ");
			foreach($usr_values as $usr_value){
				$usr_id_to_val_map[$usr_value[usr_meta_field_id]] = $usr_value[usr_meta_value];
			}

			$insert_field_array = array();
			$insert_value_array = array();
			array_push($insert_field_array , 'usr_content_id' );
			array_push($insert_value_array , "'".$content_id."'" );
			foreach($usr_id_to_val_map as $key => $val )
			{
				if( !empty($usr_id_to_name_map[$key]) ){
					array_push($insert_field_array , $usr_id_to_name_map[$key] );
					array_push($insert_value_array , "'".$db->escape($val)."'" );
				}
			}

			$new_ud_table = strtoupper("BC_USRMETA_".$ud_content_code);
			$new_sys_table = strtoupper("BC_SYSMETA_".$bs_content_code);

			$ud_field_check = $db->queryOne("select * from ".$new_ud_table." where usr_content_id='$content_id' ");
			if($ud_field_check == 1){
				//업데이트
			}else{
				//인서트
				$upquery = "insert into $new_ud_table ( ".join(' , ', $insert_field_array)." ) values ( ".join(' , ', $insert_value_array)." ) ";
				$r = $db->exec($upquery);
				file_put_contents($log_path, $upquery."\n", FILE_APPEND);
			}

			$insert_field_array = array();
			$insert_value_array = array();
			array_push($insert_field_array , 'sys_content_id' );
			array_push($insert_value_array , "'".$content_id."'" );
			foreach($sys_id_to_val_map as $key => $val )
			{
				if( !empty($sys_id_to_name_map[$key]) ){
					array_push($insert_field_array , $sys_id_to_name_map[$key] );
					array_push($insert_value_array , "'".$db->escape($val)."'" );
				}
			}

			$sys_field_check = $db->queryOne("select * from ".$new_sys_table." where sys_content_id='$content_id' ");
			if($sys_field_check == 1){
				//업데이트
			}else{
				//인서트
				$upquery = "insert into $new_sys_table ( ".join(' , ', $insert_field_array)." ) values ( ".join(' , ', $insert_value_array)." ) ";
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