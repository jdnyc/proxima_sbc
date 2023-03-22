<?php
set_time_limit(0);
define('TEMP_ROOT', '/oradata/web/nps');
require_once(TEMP_ROOT.'/lib/config.php');
require_once(TEMP_ROOT.'/migration/mig_functions.php');//마이그레이션용 함수

$GLOBALS['flag'] = '1';

$content_type_id = '506';
$cur_date = date('YmdHis');
$created_time = date('YmdHis');

define('CREATED_TIME', $created_time);

$field_mapping = array(
	4000282 => array(
		4000284	=> 4000284, //기본정보
		4000292	=> 4000292,//	프로그램
		4000293	=> 4000293, //	부제
		4000289	=> 4000289, //	방송예정일
		4000294	=> 4000294,//	내용
		4000291	=> 4000291,//	촬영일
		4000290	=> 4000290,//	촬영장소
		4000288	=> 4000288//	담당PD
	),
	4000283 => array(
		4000303 => 4000284,//	기본정보
		4000311 => 4000292,//	프로그램
		4000320 => 4000293,//	부제
		4000308 => 4000289,//	방송예정일
		4000321 => 4000294,//	내용
		4000310 => 4000291,//	촬영일
		4000309 => 4000290,//	촬영장소
		4000307 => 4000288//	담당PD
	),
	4000284 => array(
		4777824	=> 4777824,//	기본정보
		4777826	=> 4777826,//	프로그램
		4777827	=> 4777827,//	부제
		4777828	=> 4777828,//	방송예정일
		4777829	=> 4777829,//	내용
		4777837	=> 4777837,//	촬영일
		4777838	=> 4777838,//	촬영장소
		4777840	=> 4777840//	담당PD
	)
);


$log_path = TEMP_ROOT.'/log/'.basename(__FILE__).'_'.$cur_date.'.log';
$log_path_error = TEMP_ROOT.'/log/'.basename(__FILE__).'_error_'.$cur_date.'.log';
try
{
	$old_nps = new Database('ebsnps', 'ebsnps', '10.10.10.171/ebsmamdb');

	$query = " select c.* from content c , media m where c.content_id=m.content_id and m.type='thumb' and m.path!=TRIM('media_off.jpg') and c.is_deleted=0 and c.status > 0 ";
	$order = " order by c.content_id asc ";
	

//	//분할 병렬 작업을 위해
//	$cut_start = 0;
//	$cut_limit = 40000;
//	$query = " SELECT * FROM (SELECT mdb2tb.*, ROWNUM mdb2rn2 FROM ( ".$query.$order." ) mdb2tb WHERE ROWNUM <= $cut_limit ) WHERE mdb2rn2 > $cut_start " ;
	
	//방송자료 전체 로우
	$total = $old_nps->queryOne("select count(*) from ( $query  ) cnt ");

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
		$old_nps->setLimit($limit , $start);
		$lists = $old_nps->queryAll($query.$order);

		foreach( $lists as $list )
		{
			echo $total."/".$j++."\r";//확인

			$old_content_id = $list['content_id'];

				
			$UDMetaList = array();//$db->queryAll("select * from BC_USR_META_FIELD where ud_content_id ='$meta_table_id' order by show_order  ");

			$systemMetaList = $old_nps->queryAll("select * from content_value where content_id='$old_content_id'");
			$media_list = $old_nps->queryAll("select * from media where content_id='$old_content_id' order by media_id ");
			$scene_list = $old_nps->queryAll("select s.* from SCENE s , media m where s.media_id=m.media_id and m.type='original'  and m.content_id='$old_content_id'  order by s.SCENEID ");

			$UDMetaOldData = $old_nps->queryAll("select * from meta_value where content_id='$old_content_id'  order by meta_field_id ");

			$content_id = insertContent($list);
			insertCodeInfo($content_id );

			insertSystemMeta($content_type_id ,$content_id, $systemMetaList);
			
			insertUDMeta($content_id,  $UDMetaOldData, $field_mapping  );
			
			insertMedia($content_id , $media_list );

			
			$media_id = $db->queryOne("select media_id from bc_media where content_id='$content_id' and media_type='original'");


			insertScene($content_id,$media_id ,$scene_list);
			

			$description = '마이그레이션 - '.$old_content_id.' => '.$content_id;

				file_put_contents($log_path, date("Y-m-d H:i:s").$description."\n", FILE_APPEND);

			insertLog('regist', $list['user_id'], $content_id, $description);

			//콘텐츠 정보 입력
			//미디어 정보 입력
			//시스템 정보 입력
			//사용자메타 정보 입력

		}
	}

	file_put_contents($log_path, '종료 : '.date("Y-m-d H:i:s")."\n", FILE_APPEND);
}

catch ( Exception $e )
{
	file_put_contents($log_path_error, date("Y-m-d H:i:s").' '.$e->getMessage().' '.$db->last_query."\n", FILE_APPEND);
	echo $e->getMessage().' '.$db->last_query;	
}


function insertLog($action, $user_id, $content_id, $description)
{
	global $db;

	if(!empty($description))
	{
		$description = $db->escape($description);
	}

	$cur_datetime = date('YmdHis');
	$con_info = $db->queryRow("select bs_content_id, ud_content_id from bc_content where content_id = '$content_id'");

	$result = $db->exec("insert into bc_log (action, user_id, bs_content_id, ud_content_id, content_id, created_date, description) values ('$action', '$user_id','{$con_info['bs_content_id']}', '{$con_info['ud_content_id']}', '$content_id', '$cur_datetime', '$description')");
}

?>