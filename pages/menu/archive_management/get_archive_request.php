<?php
set_time_limit(0);
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$limit =		$_POST['limit'];
$start =		$_POST['start'];
$arc_s_date =		$_POST['arc_start_date'];
$arc_e_date =		$_POST['arc_end_date'];

$delete_combo = $_POST['delete_combo'];
$mtrl_id = $_POST['mtrl_id'];

$arc_type = $_POST['arc_type'];
$arc_status = $_POST['arc_status'];

$groups = $_SESSION['user']['groups'];
$search_field  = $_REQUEST['search_field'];

if(empty($start)){
    $start = 0;
}
if(empty($limit)){
    $limit = 100;
}

$mappingMetaTable = array(
	UD_NDS =>	'NDS 아카이브',
	UD_PDS =>	'PDS 아카이브'
);

//현재 유저의 content 테이블 불러오기
try
{	
	$today = date('Ymd');
	$e_date = date('Ymd', strtotime($arc_e_date.'-1 days'));
	$s_date = date('Ymd', strtotime($arc_s_date));
	if($e_date > $today) {
		$start_dt = $s_date;
		$end_dt = $today;
	} else {
		$start_dt = $s_date;
		$end_dt = $e_date;
	}

//	$mode = 'GetDunetArchiveList';
//	$data = array(
//		//'date' => '20140407'
//		//'date' => $today
//		'date' => $date_param
//	);
//	require($_SERVER['DOCUMENT_ROOT'].'/interface/app/client/common.php');
//	$datas = $include_return;

	//아카이브 리스토어 요청시엔 백그라운드로 작업 돌리기.
//	$url = 'http://'.SERVER_IP_DAS.'/store/update_archive_request_by_background.php';
//	$params = array(
//		'start_dt' => $start_dt,
//		'end_dt' => $end_dt
//	);
//	request_async($url, $params);

	switch($arc_type){
		case '전체':
		case 'all':
			$arc_type_query = "";
			break;
		default :
			//그 외 arc_type 들
			$arc_type_query = " and car.arc_type='".$arc_type."' ";
			break;
	}

	switch($arc_status){
		case '전체':
		case 'all':
			$arc_status_query = "";
			break;
		default :
			//그 외 arc_type 들
			$arc_status_query = " and car.status='".$arc_status."' ";
			break;
	}

	if( !empty($mtrl_id) )
	{
		$mtrl_id = $db->escape($mtrl_id);

		switch($search_field)
		{
			case '2':
				$mtrl_id_query = " and (car.REQ_USER_ID like '%".$mtrl_id."%' or car.USER_NM like '%".$mtrl_id."%') ";
			break;

			case '3':
				$mtrl_id_query = " and (car.ARC_USER_ID like '%".$mtrl_id."%' or car.arc_user_id in ((SELECT USER_id FROM BC_MEMBER WHERE user_nm like '%".$mtrl_id."%')) ) ";
			break;

			default :
				$mtrl_id_query = " and (car.mtrl_id like '%".$mtrl_id."%'
										or car.title like '%".$mtrl_id."%'
										or car.mgmt_id like '%".$mtrl_id."%') ";
			break;
		}		
	}
	
	

	$arr_ud_content_query = array();
	if( in_array(NDS_ARCHIVE_GROUP, $groups) ) {
		array_push($arr_ud_content_query, " c.ud_content_id='".UD_NDS."' ");
	}
	if( in_array(PDS_ARCHIVE_GROUP, $groups) ) {
		array_push($arr_ud_content_query, " c.ud_content_id='".UD_PDS."' ");
	}
	if( !empty($arr_ud_content_query) ) {
		$ud_content_query = " and (".implode(" or ", $arr_ud_content_query).") ";
	}
	
	$arc_date_query = "and car.req_time >= '".$arc_s_date."' and car.req_time <= '".$arc_e_date."'";
//2015.01.06 윤성욱
//승수씨 페이지 보기위해서 제가 임의로 넣어놨습니다. -> and arc_type is not null 
/*
	$query = "
		select car.*, c.ud_content_id			
		from cha_archive_request car, bc_content c
		where car.content_id=c.content_id
		  and car.req_no is not null
		  and arc_type is not null 
		 ".$arc_date_query.$mtrl_id_query.$arc_type_query.$arc_status_query.$ud_content_query;
*/
	$query ="

				SELECT       car.*
							,c.UD_CONTENT_ID
							,(
							  SELECT USER_NM 
							  FROM   BC_MEMBER 
							  WHERE  UPPER(user_id) = UPPER(car.arc_user_id)
							 ) as ARC_USER_NM

				FROM         CHA_ARCHIVE_REQUEST car
							,BC_CONTENT c

				WHERE       car.CONTENT_ID = c.CONTENT_ID
							AND car.REQ_NO is not null
							AND car.ARC_TYPE is not null
				".$arc_date_query.$mtrl_id_query.$arc_type_query.$arc_status_query.$ud_content_query;

	$total_query = "select count(*) from (".$query.") t1";
  // file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ss_query2.html', date("Y-m-d H:i:s\t").$query."\n\n", FILE_APPEND);
	$total = $db->queryOne($total_query);

	//요청일자로 정렬
	$db->setLimit($limit,$start);
	$results = $db->queryAll($query." order by car.req_time desc, car.mtrl_id");

	$cur_date = date('YmdHis');
	$arr_content_id = array();
	$arr_mgmt_id = array();
	$arr_req_status_info = array();
	$arr_req_type_info   = array();
	$arr_req_type_pos    = array();
	$arr_req_done_time   = array();
	$i=0;


	

	foreach($results as $res){
		$req_user_id = $res['req_user_id'];
		$req_user_nm = $res['user_nm'];
		$results[$i]['req_user_info'] = $req_user_nm."(".$req_user_id.")";
		
		$arc_user_id  = $res['arc_user_id'];
		 $arc_user_nm = $res['arc_user_nm'];
		if(!empty($arc_user_id))
		{		
			$results[$i]['arc_user_info'] = $arc_user_nm."(".$arc_user_id.")";
		}
		else 
		{
			$results[$i]['arc_user_info'] ="";
		}

		$arr_content_id[] = $res['content_id'];
		$arr_mgmt_id[] = $res['mgmt_id'];
		$arr_req_status_info[$res['mgmt_id']] = $res['status'];
		$arr_req_type_info[$res['mgmt_id']] = $res['arc_type'];
		$arr_req_type_pos[$res['mgmt_id']] = $i;
		$arr_req_done_time[$res['mgmt_id']] =  $res['done_time'];
		//$arr_req_mtrl_info[$res['mgmt_id']] = $res['mtrl_id'];
		$results[$i]['job_progress'] = "";
		$i++;
	}

	$content_id_in = implode(',', $arr_content_id);
	$mgmt_id_param = implode("','", $arr_mgmt_id);
	$mode = 'MtrlStatus';
	$data = array(
		'mgmt_ids' => $mgmt_id_param
	);
	require($_SERVER['DOCUMENT_ROOT'].'/interface/app/client/common.php');
	$datas2 = $include_return['result']['return'];
	$datas2 = json_decode($datas2, true);
	$datas2 = $datas2[0]['data'];
	
	$now = date('Y-m-d');
	//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/arr_req_status_info_'.$now.'.log', date("Y-m-d H:i:s\t").print_r($datas2,true)."\n\n", FILE_APPEND);
	if(is_array($datas2)) {
		foreach($datas2 as $d) {
			$mtrl_id = $d['mtrl_id'];
			$mgmt_id = $d['mgmt_id'];
			$req_status = (int)$d['req_status'];
			$new_mtrl_id = $d['new_mtrl_id'];
			$done_time   = $d['done_time'];
			if($done_time)
			{
				$done_time = substr($done_time,0,14);
			}
			
			$done_time_query = "";

			if($req_status == 4)
			{
				$done_time_query = ", DONE_TIME = '$done_time' ";
			}

			
			//추가 현재 진행률
			$job_progress = $d['job_progress'];
			$pos = $arr_req_type_pos[$mgmt_id];

			$results[$pos]['job_progress'] = $job_progress;

			if($job_progress && $job_progress != "100" &&($results[$pos]['status'] == "5" || $results[$pos]['status'] == "3") )
			{
				$results[$pos]['status'] = $job_progress;
			}

			//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/arr_req_status_info.log', date("Y-m-d H:i:s\t").print_r($arr_req_status_info,true)."\n\n", FILE_APPEND);
			$ori_req_status = $arr_req_status_info[$mgmt_id];
			$ori_arc_type   = $arr_req_type_info[$mgmt_id];
			$log_str = "Mtrl_id : $mtrl_id | mgmt_id : $mgmt_id => original_status :".$ori_req_status." return status : ".$req_status." job_progress : ".$job_progress;
			//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/arr_req_status_info_'.$now.'.log', date("Y-m-d H:i:s\t").$log_str."\n\n", FILE_APPEND);
			

		


			if( $ori_req_status > 10
			 || $ori_req_status == $req_status) {
				//Tape쪽 작업 값이 들어가 있다면 패스.
				//이전값과 현재값이 같으면 패스.
				if($arr_req_done_time[$mgmt_id]  != $done_time && $req_status == 4)
				{
					//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/arr_req_status_info_'.$now.'.log', date("Y-m-d H:i:s\t")."UPDATE DONE_TIME !"."\n\n", FILE_APPEND);

					$db->exec("update cha_archive_request set						
						 DONE_TIME = '$done_time'
					where mgmt_id='".$mgmt_id."'");
				}
				continue;
			}
			else if($req_status == 4 && $ori_arc_type == 'archive' && $ori_req_status != $req_status && !empty($mtrl_id))
			{		
				/*
				$update_query = "
							UPDATE CONTENT_CODE_INFO
							SET    ARC_TIME ='".$now."'
							WHERE  MTRL_ID = '$mtrl_id' and ARC_TIME IS NULL
				";
				*/
				$mode = 'GetMtrlInfoUpdateInfo';
				$data = $mtrl_id;				
				require($_SERVER['DOCUMENT_ROOT'].'/interface/app/client/common.php');
				$log_filenm = "request_mtrl_icms_accept_".date('Y-m-d').".log";
				$log_str2 ="\n\nQUERY :".$update_query."\n\n";

				//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.$log_filenm, date("Y-m-d H:i:s\t").$log_str.$log_str2."\n\n", FILE_APPEND);
				//$db->exec($update_query);

				$db->exec("update cha_archive_request set
						status='".$req_status."'
						$done_time_query
					where mgmt_id='".$mgmt_id."'");

				/* 추후 사항 
					리스트 목록을 줄 때.. 업데이트 된 정보를 줘야하나?
						*/
				$pos = $arr_req_type_pos[$mgmt_id];
				if(!empty($pos))
				{
					//$results[$pos]['status']=$req_status;
				}
				
			}			
			else {
				
				if($new_mtrl_id == "")
				{
					$db->exec("update cha_archive_request set
							status='".$req_status."'
							$done_time_query
						where mgmt_id='".$mgmt_id."'");
				}
				else 
				{
					$update_query = "
						UPDATE CHA_ARCHIVE_REQUEST
						SET STATUS = '".$req_status."',
						    NEW_MTRL_ID ='".$new_mtrl_id."'
							$done_time_query
						WHERE  mgmt_id='".$mgmt_id."'
					";
					$db->exec($update_query);
				}
			}

			
		}
	}

	

	$arr_info_msg = getStoragePolicyInfo();
	$info = $arr_info_msg['info1_2'];
	$info2 = $arr_info_msg['info2_2'];


	$db->close();

	$data = array(
		'success'		=> true,
		'data'			=> $results,
		'total_list'	=> $total,
		'query'			=> $query,
		'temp'			=> $tt,
		'info'			=> $info,
		'info2'			=> $info2
	);

	echo json_encode($data);	

}
catch (Exception $e)
{
	$data = array(
		'success'	=> false,
		'msg'		=> $e->getMessage(),
		'query' => $db->last_query
	);

	echo json_encode($data);
}
?>
