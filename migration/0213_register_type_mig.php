<?php
set_time_limit(0);
define('TROOT', '/oradata/web/nps');
require_once(TROOT.'/lib/config.php');
require_once(TROOT.'/lib/functions.php');
$created =date('Ymd');
$filename =basename(__FILE__);

define('CREATED_TIME', date('YmdHis'));

$limit = 3000;
try
{
	$query = " select c.content_id,c.ud_content_id, c.reg_user_id from  content_code_info cc,bc_content c where c.content_id=cc.content_id and  cc.register_type is  null and  c.ud_content_id in (4000282,
4000284,
4000345,
4000346,
4000365,
4000385)";

	$order = '  order by c.content_id asc  ';
	$total = $db->queryOne("select count(*) from ( $query ) cnt ");
	$j =0 ;
	$total_list = array();

	echo $log_msg = '['.date("Y-m-d H:i:s").'] '.$total.' data DB Loading...'."\n";//확인
	@file_put_contents(TROOT.'/log/'.$filename.$created.'.log', $log_msg."\n", FILE_APPEND);

	for($start = 0 ; $start< $total ; $start+=$limit)
	{
		$db->setLimit($limit, $start);
		$lists = $db->queryAll($query.$order);

		foreach( $lists as $key => $row )
		{
			echo $total."/".$j++."\r";
			$content_id = $row['content_id'];
			$ud_content_id = $row['ud_content_id'];
			$reg_user_id = $row['reg_user_id'];
			unset($task_check);
			unset($register_type);
			switch($ud_content_id)
			{
				case UD_DASDOWN:
					$register_type ='D';
				break;
				case UD_CM://CM
					$register_type ='C';
				break;
				default:
					$task_check = $db->queryRow("select * from (
					select * from bc_task where media_id in (select media_id from bc_media where content_id='$content_id' ) order by task_id asc
					) t where rownum = 1");
					

					$t_reg_user_id = strtoupper($reg_user_id);
					$is_user = $db->queryOne("select count(*) from bc_member where upper(user_id)='$t_reg_user_id'");

					if( strstr( $task_check['destination'] , 'fcp') ){
						$register_type ='E';
					}else if(  strstr( $task_check['destination'] , 'edius') ){
						$register_type ='E';
					}else if( strstr( $task_check['destination'] , '1' ) ||  strstr( $task_check['destination'] , '2' ) ||  strstr( $task_check['destination'] , '3' ) ||  strstr( $task_check['destination'] , '4' ) ||  strstr( $task_check['destination'] , '5' ) ||  strstr( $task_check['destination'] , '6' ) || ( $task_check['destination'] == 'space' ) ){
						$register_type ='I';
					}else if( $reg_user_id == 0 || $reg_user_id == 'space' ){
						$register_type = 'I';
					
					}else if ( $is_user > 0 ){
						$register_type = 'E';
					}
				break;
			}
			
			

			if(empty($register_type)){
				$register_type = 'I';
				$log_msg = '['.date("Y-m-d H:i:s").'] '.$content_id.','.$task_check['destination'].','.$reg_user_id.','.'알수없음';//확인
				@file_put_contents(TROOT.'/log/'.$filename.$created.'_un.log', $log_msg."\n", FILE_APPEND);
			}

			$r = $db->exec("update content_code_info set register_type ='$register_type' where content_id='$content_id' ");

			$log_msg = '['.date("Y-m-d H:i:s").'] '.$content_id.' : '.$task_check['destination'].' : '.$register_type;//확인
			@file_put_contents(TROOT.'/log/'.$filename.$created.'.log', $log_msg."\n", FILE_APPEND);

			//이제 I, E 둘중 하나인데..
			// 구분 법은 작업 등록자 
			//인제스트는 1~6
			//FCP 는 fcp
			//에디우스는 edius			
		}
	}

	echo $log_msg = '['.date("Y-m-d H:i:s").'] '.$total.' data DB Loaded...';//확인
	@file_put_contents(TROOT.'/log/'.$filename.$created.'.log', $log_msg."\n", FILE_APPEND);

}
catch(Exception $e)
{
	echo $e->getMessage().' '.$db->last_query;
	@file_put_contents(TROOT.'/log/migration/'.basename(__FILE__).CREATED_TIME.'_error_.log', date("Y-m-d H:i:s").' '.$e->getMessage().' '.$db->last_query."\n", FILE_APPEND);
}

?>