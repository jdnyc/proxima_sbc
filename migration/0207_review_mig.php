<?php
set_time_limit(0);
define('TROOT', '/oradata/web/nps');
require_once(TROOT.'/lib/config.php');
require_once(TROOT.'/lib/functions.php');
$created =date('Ymd');
$filename =basename(__FILE__);

define('CREATED_TIME', date('YmdHis'));

$limit = 2000;
try
{
	$query = " select * from NPS_WORK_LIST where work_type='review' ";

	$order = '  order by nps_work_list_id asc  ';
	$total = $db->queryOne("select count(*) from ( $query ) cnt ");
	$j =0 ;
	$total_list = array();

	echo $log_msg = '['.date("Y-m-d H:i:s").'] '.$total.' data DB Loading...'."\n";//확인
	@file_put_contents(TROOT.'/log/migration/'.$filename.$created.'.log', $log_msg."\n", FILE_APPEND);

	for($start = 0 ; $start< $total ; $start+=$limit)
	{
		$db->setLimit($limit, 0);
		$lists = $db->queryAll($query.$order);

		foreach( $lists as $key => $row )
		{
			echo $total."/".$j++."\r";
			$nps_work_list_id = $row['nps_work_list_id'];
			$content_id = $row['content_id'];

			$to_user_id = $row['to_user_id'];
			$from_user_id = $row['from_user_id'];

			$created_date = $row['created_date'];//신청일자

			$meta_list = $db->queryAll("select * from BC_USR_META_VALUE where content_id='$content_id' ");
			$meta_map = array();
			foreach($meta_list as $list)
			{
				$meta_map [$list['usr_meta_field_id']] = $list['usr_meta_value'];
			}

			$to_user_info = $db->queryRow("select * from bc_member where user_id='$to_user_id' ");
			$from_user_info = $db->queryRow("select * from bc_member where user_id='$from_user_id' ");


			$prognm = $db->escape($meta_map['4000292']);//프로그램명 4000292
			$subprognm = $db->escape($meta_map['4000293']);//부제 4000293
			$reqymd = date("Ymd", strtotime($created_date) );
			$brodymd = empty($meta_map['4000289']) || !strtotime($meta_map['4000289']) ? '' : date( 'Ymd', strtotime($meta_map['4000289']) );//방송일자 4000289
			$direction = $db->escape( $meta_map['4000288'] );//담당PD 4000288
			$review_user = $to_user_info['user_nm'];
			
			$dept_nm = $from_user_info ['dept_nm'];
			$pd_nm =  $db->escape( $meta_map['4000288'] );//담당PD 4000288;
		
			$grade =  $db->escape( $meta_map['4778141'] );//등급분류
			
			$reviewymd_temp = $db->queryOne("select max(created_date) from bc_log where action='review' and content_id='$content_id'") ;
			$reviewymd = empty($reviewymd_temp) || !strtotime($reviewymd_temp) ? '' : date( 'Ymd', strtotime($reviewymd_temp) );

			$review_type = 'FILE';
			$producer='';
	
		
			$after='';
			$not_review='';
			$reason='';
			$tapeno='';		
			
			if( !empty($brodymd) && !empty($reqymd) ){					
				//방송일로부터 요청일이 3일전이면 지연 없음 그 후 +1
				$turm =  ( strtotime($brodymd) - strtotime($reqymd) ) / (24*60*60) ;	
				
				if( $turm == 0 ) //방송일과 요청일이 같으면 당일
				{
					$delay = '당일';
				}
				else if( ( $turm == 1 ) || ( $turm == 2 ) ) //방송일과 요청일이 같으면 당일
				{
					$delay = $turm.' 지연';
				}
				else if( $turm < 0 )
				{
					$delay = '';
					$after = '사후';
				}
				else
				{
					$delay ='';
				}
			}

			$exist_nps_review_row = $db->queryRow("select * from NPS_REVIEW where NPS_WORK_LIST_ID ='$nps_work_list_id' ");
			if( !empty($exist_nps_review_row) ){
				$r = $db->exec("update NPS_REVIEW set 
						prognm='$prognm',
						subprognm='$subprognm',
						dept_nm='$dept_nm',
						pd_nm='$pd_nm',
						review_user='$review_user',
						grade='$grade',
						reviewymd='$reviewymd',
						brodymd='$brodymd',
						review_type='$review_type',
						producer='$producer' 
						where NPS_WORK_LIST_ID ='$nps_work_list_id' ");
			}else{
				$r = $db->exec("insert into NPS_REVIEW (NPS_WORK_LIST_ID,PROGNM,SUBPROGNM,DEPT_NM,PD_NM,REVIEW_USER,GRADE,REVIEWYMD,BRODYMD,REVIEW_TYPE,PRODUCER) values ('$nps_work_list_id','$prognm','$subprognm','$dept_nm','$pd_nm','$review_user','$grade','$reviewymd','$brodymd','$review_type','$producer') ");
			}

			$content_info = $db->queryRow("select * from view_content where content_id='$content_id'");

			$medcd = $content_info['medcd'];
			$progcd = $content_info['progcd'];
			$subprogcd = $content_info['subprogcd'];
			$formbaseymd = $content_info['formbaseymd'];

			$exist_nps_review_daily_row = $db->queryRow("select * from NPS_REVIEW_DAILY where NPS_WORK_LIST_ID ='$nps_work_list_id' ");
			if( !empty($exist_nps_review_row) ){
				$r = $db->exec("update NPS_REVIEW_DAILY set PROGNM='$prognm',SUBPROGNM='$subprognm',REQYMD='$reqymd',BRODYMD='$brodymd',DIRECTION='$direction',REVIEW_USER='$review_user',AFTER='$after',REVIEWYMD='$reviewymd',DELAY='$delay',MEDCD='$medcd',
				PROGCD='$progcd',
				SUBPROGCD='$subprogcd',
				FORMBASEYMD='$formbaseymd' where NPS_WORK_LIST_ID ='$nps_work_list_id'  ");
			}else{
				
				$nps_review_daily_id = getSequence('SEQ_NPS_REVIEW_DAILY_ID');

				$r = $db->exec("insert into NPS_REVIEW_DAILY (NPS_REVIEW_DAILY_ID,PROGNM,SUBPROGNM,REQYMD,BRODYMD,DIRECTION,REVIEW_USER,AFTER,NOT_REVIEW,REASON,TAPENO,REVIEWYMD,NPS_WORK_LIST_ID,DELAY,MEDCD,
				PROGCD,
				SUBPROGCD,
				FORMBASEYMD) values ('$nps_review_daily_id','$prognm','$subprognm','$reqymd','$brodymd','$direction','$review_user','$after','$not_review','$reason','$tapeno','$reviewymd','$nps_work_list_id','$delay','$medcd','$progcd','$subprogcd','$formbaseymd') ");
			}
		}
	}

	echo $log_msg = '['.date("Y-m-d H:i:s").'] '.$total.' data DB Loaded...'."\n";//확인
	@file_put_contents(TROOT.'/log/migration/'.$filename.$created.'.log', $log_msg."\n", FILE_APPEND);

}
catch(Exception $e)
{
	echo $e->getMessage().' '.$db->last_query;
	@file_put_contents(TROOT.'/log/migration/'.basename(__FILE__).CREATED_TIME.'_error_.log', date("Y-m-d H:i:s").' '.$e->getMessage().' '.$db->last_query."\n", FILE_APPEND);
}

?>