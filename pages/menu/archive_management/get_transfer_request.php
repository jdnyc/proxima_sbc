<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$user_id = $_SESSION['user']['user_id'];

$limit =		$_POST['limit'];
$start =		$_POST['start'];
$arc_s_date =		$_POST['arc_start_date'];
$arc_e_date =		$_POST['arc_end_date'];

$delete_combo = $_POST['delete_combo'];
$mtrl_id = $_POST['mtrl_id'];
$req_status = $_POST['req_status'];


if(empty($start)){
    $start = 0;
}
if(empty($limit)){
    $limit = 100;
}

try
{	
	/*
		권한체크*/

	$grant = CHA_GRANT_TRANSFER_AUTH;
	$auth_flag_arr = array(0,0);

	$user_query_str = " req_user_id = '".$user_id."' ";

	if(checkTransGrant($user_id , UD_NDS , $grant))
	{
		$auth_flag_arr[0] = 1;
	}
	
	if(checkTransGrant($user_id , UD_PDS , $grant))
	{
		$auth_flag_arr[1] = 1;
	}
	
	if($auth_flag_arr[0] == 1  && $auth_flag_arr[1] == 1)
	{
		$type_query_str = "";
	}
	else if($auth_flag_arr[0] == 1)
	{
		$type_query_str = " and MTRL_TP = '".UD_NDS."' ";
	}
	else if($auth_flag_arr[1] == 1)
	{
		$type_query_str = " and MTRL_TP = '".UD_PDS."' ";
	}

	$req_type = $_POST['req_type'];

	switch($req_type){
		case '전체':
		case 'all':
			$req_type_query = "";
			break;
		default :
			//그 외 req_type 들
			$req_type_query = " and ctr.req_type='".$req_type."' ";
			$user_query_str .= " and ctr.req_type='".$req_type."' ";
			break;
	}

	switch($req_status){
		case '전체':
		case 'all':
			$req_status_query = "";
			break;
		default :
			//그 외 req_type 들
			$req_status_query = " and ctr.req_status ='".$req_status."' ";
			$user_query_str .=  " and ctr.req_status ='".$req_status."' ";
			break;
	}

	if( !empty($mtrl_id) )
	{
		$mtrl_id = $db->escape($mtrl_id);
		$mtrl_id_query = " and ctr.mtrl_id like '%".$mtrl_id."%' ";
		$user_query_str .=  " and ctr.mtrl_id like '%".$mtrl_id."%' ";
	}
	
	$arc_date_query = "ctr.req_time >= '".$arc_s_date."' and ctr.req_time <= '".$arc_e_date."'";

	$user_last_query = "AND ((".$user_query_str." and ".$arc_date_query." ) or ( ".$arc_date_query.$mtrl_id_query.$req_type_query.$type_query_str.$req_status_query."))";

	$query = "
		select ctr.* ,
			(select user_nm from bc_member where user_id = ctr.req_user_id) as user_nm2,
			(select user_nm from bc_member where user_id = ctr.auth_user_id) as auth_user_nm2,
			t.status as task_status,
			t.progress
		from cha_transfer_request ctr  left outer join bc_task t on  t.media_id = 0 and t.cha_req_no = ctr.req_no
		where ctr.req_type is not null 
		      AND del_yn = 'N'
		 ".$user_last_query;


	$total_query = "select count(*) from (".$query.") t1";
	$total = $db->queryOne($total_query);

	//요청일자로 정렬
	$db->setLimit($limit,$start);
	$results = $db->queryAll($query." order by ctr.req_time desc");
//print_r($query);exit;
	$cur_date = date('YmdHis');
	$arr_content_id = array();
	$i=0;
	foreach($results as $res){

		$arr_content_id[] = $res['content_id'];
		if($res['user_nm2'])
		{
			$results[$i]['user_info'] = $res['user_nm2']."(".$res['req_user_id'].")";
		}
		else 
		{
			$results[$i]['user_info'] = $res['req_user_id'];
		}

		if($res['auth_user_nm2'])
		{
			$results[$i]['auth_user_info'] = $res['auth_user_nm2']."(".$res['auth_user_id'].")";
		}
		else 
		{
			$results[$i]['auth_user_info'] = $res['auth_user_id'];
		}

		if($results[$i]['task_status'] == 'progress' || $results[$i]['task_status'] == 'processing')
		{
			$results[$i]['task_status'] = $results[$i]['progress'];
		}

		if($results[$i]['mtrl_tp'] == UD_NDS)
		{
			$results[$i]['ud_content_title'] = '보도';
		}
		else if($results[$i]['mtrl_tp'] == UD_PDS)
		{
			$results[$i]['ud_content_title'] = '제작';
		}

		if(!$results[$i]['transfer_expired_date'])
		{
			$results[$i]['transfer_expired_date'] = '-';
		}
		else 
		{
			$results[$i]['transfer_expired_date'] = $results[$i]['transfer_expired_date']."일 후";
		}
		
		$i++;
	}
	$content_id_in = implode(',', $arr_content_id);
//	print_r($content_id_in);exit;

	foreach($results as $f => $v){
		
	}

	$arr_info_msg = getStoragePolicyInfo();
	$info = $arr_info_msg['info1_2'];
	$info2 = $arr_info_msg['info2_2'];

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