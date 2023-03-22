<?php
set_time_limit(0);
define('TEMP_ROOT', '/oradata/web/nps');
require_once(TEMP_ROOT.'/lib/config.php');


$GLOBALS['flag'] = '1';

$content_type_id = '506';
$cur_date = date('YmdHis');
$created_time = date('YmdHis');

define('CREATED_TIME', $created_time);

$log_path = TEMP_ROOT.'/log/'.basename(__FILE__).'_'.$cur_date.'.log';
$log_path_error = TEMP_ROOT.'/log/'.basename(__FILE__).'_error_'.$cur_date.'.log';
try
{
	$query = "select * from bc_member  ";
	$order = "  order by user_id  "; 
	
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
			echo $total."/".$j++."\r";//확인
			if( $list['user_id'] == 'admin' ) continue;

			$user_id = $list['user_id'];

			$check = $dbDas->queryRow("select * from member where user_id='$user_id' ");
			if( !empty($check) )
			{
				$password = $check['password'];
				$email  = $check['email'];
				$uquery = " update bc_member set password='$password' , email='$email' where user_id='$user_id' ";
				file_put_contents($log_path, '업데이트: ,'.$uquery."\n", FILE_APPEND);

				$r = $db->exec($uquery);
			}
			else
			{
				file_put_contents($log_path, 'DAS에 없는 ID: ,'.$list['user_id']."\n", FILE_APPEND);
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