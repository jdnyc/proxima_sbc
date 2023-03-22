<?php
set_time_limit(0);
define('TEMP_ROOT', '/oradata/web/nps');
require_once(TEMP_ROOT.'/lib/config.php');
require_once(TEMP_ROOT.'/lib/functions.php');
//require_once(TEMP_ROOT.'/migration/mig_functions.php');//마이그레이션용 함수

$GLOBALS['flag'] = '1';

$content_type_id = '506';
$cur_date = date('YmdHis');
$created_time = date('YmdHis');

define('CREATED_TIME', $created_time);

$log_path = TEMP_ROOT.'/log/'.basename(__FILE__).'_'.$cur_date.'.log';
$log_path_error = TEMP_ROOT.'/log/'.basename(__FILE__).'_error_'.$cur_date.'.log';
try
{

	$query = " select c.*, m.path from bc_content c, bc_media m where c.content_id=m.content_id and m.media_type='original' and c.ud_content_id in ( ".join(',',$CG_LIST)." ) ";
	$order = " order by c.content_id asc ";
	
	//방송자료 전체 로우
	$total = $db->queryOne("select count(*) from ( $query  ) cnt ");

	$limit = 2000;
	
	$j = 0;
	$real_c = 0;
	$err_real_c = 0;

	file_put_contents($log_path, '시작 : '.date("Y-m-d H:i:s")."\n", FILE_APPEND);
	file_put_contents($log_path, '실제 마이그레이션 대상 수 : '.$total."\n", FILE_APPEND);
	
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

			$content_id = $list['content_id'];
			$bs_content_id = $list['bs_content_id'];
			$ud_content_id = $list['ud_content_id'];
			$original_path = $list['path'];
			update_ext_info( $content_id,$bs_content_id, $ud_content_id, $original_path );
		
			//file_put_contents($log_path, '변경,'.$content_id."\n", FILE_APPEND);
			
		}
	}

	file_put_contents($log_path, '종료 : '.date("Y-m-d H:i:s")."\n", FILE_APPEND);
}

catch ( Exception $e )
{
	file_put_contents($log_path_error, date("Y-m-d H:i:s").' '.$e->getMessage().' '.$db->last_query."\n", FILE_APPEND);
	echo $e->getMessage().' '.$db->last_query;	
}

function update_ext_info( $content_id,$bs_content_id, $ud_content_id, $original_path )
{
	global $db;

	$update_list = $db->queryRow("select * from BC_USR_META_FIELD where ud_content_id='$ud_content_id' and TRIM(USR_META_FIELD_TITLE)='확장자' ");
	if( !empty($update_list) )
	{
		$usr_meta_field_id = $update_list['usr_meta_field_id'];

		if( $bs_content_id == SOUND ||  $bs_content_id  == MOVIE )
		{//영상,음원만 원본패스에서 추출
			//원본패스 배열
			$path_array = explode('/',$original_path);
			
			if(count($path_array) < 1) return false;
			
			//파일명
			$filename = array_pop($path_array);
			
			//파일명과 확장자 분리
			$filename_array = explode('.',$original_path);
			
			if(count($filename_array) < 2) return false;

			$ext = $db->escape(strtoupper(array_pop($filename_array)));
		}
		else
		{
			$format_info = $db->queryRow("select * from BC_SYS_META_FIELD where bs_content_id='$bs_content_id' and sys_meta_field_title like '%포맷%' ");
			if( !empty($format_info) )
			{			
				$format_value = $db->queryOne("select SYS_META_VALUE from BC_SYS_META_VALUE where content_id='$content_id' and SYS_META_FIELD_ID='{$format_info['sys_meta_field_id']}' ");
				$ext = $db->escape(strtoupper($format_value));
			}
		}

		$field_check = $db->queryRow("select * from bc_usr_meta_value where  content_id='$content_id' and ud_content_id='$ud_content_id' and USR_META_FIELD_ID='$usr_meta_field_id' ");
		if( empty($field_check) )
		{
			$u_query = "insert into bc_usr_meta_value (content_id, ud_content_id, usr_meta_field_id, usr_meta_value ) values 	($content_id, $ud_content_id, $usr_meta_field_id, '$ext')";			
		}
		else
		{
			$u_query = "update bc_usr_meta_value set USR_META_VALUE='$ext' where content_id='$content_id' and ud_content_id='$ud_content_id' and USR_META_FIELD_ID='$usr_meta_field_id' ";
		}
		$r = $db->exec($u_query);
	}
	return true;
}
?>