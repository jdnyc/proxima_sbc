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

	$query = " select c.* from bc_content c where c.ud_content_id='".UD_ARCHIVE."' ";
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
			file_put_contents($log_path, '변경,'.$content_id."\n", FILE_APPEND);
			ud_content_change($content_id , UD_PAS );
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