<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$user_id = $_SESSION['user']['user_id'];
$mode = $_POST['mode'];
$content_id = $_POST['content_id'];
$s_date = $_POST['s_date'];
$e_date = $_POST['e_date'];
$s_date = date('Ymd', strtotime($s_date)).'000000';
$e_date = date('Ymd', strtotime($e_date)).'235959';
$search_word = $_POST['search_word'];
$status = $_POST['status'];
$is_nle = $_POST['is_nle'];
$limit =		$_POST['limit'];
$start =		$_POST['start'];
if(empty($start)){
    $start = 0;
}
if(empty($limit)){
    $limit = 50;
}

try
{
	if($user_id == '' || $user_id == 'temp')
	{
		throw new Exception('Please login again.');
	}	

	$topic_q = "";
	$content_q = "";
	$brod_q = " and c.expired_date between '".$s_date."' and '".$e_date."'";

	//검색어가 있으면, 필터링되야 하므로. 검색어 없다면 조인
	if( !empty($search_word) ) {
		//$topic_q = " and UPPER(usr_meta_value) like '%".strtoupper($search_word)."%' ";
		$topic_q = " and UPPER(c.title) like '%".mb_convert_case($search_word, MB_CASE_UPPER, "UTF-8")."%' ";
	}

	if( !empty($status) ) {
		$status_q = " and c.manager_status like '%".$status."%' ";
	}

	//NLE에서 조회할 시, 승인된 항목만 보여야한다.
	if( $is_nle == 'true' ) {
		$status_q_nle = " and c.manager_status = 'accept' ";
	}

	//검색속도 높이기 위해 count는 따로
	$total_query = "select count(c.content_id)
		from bc_content c, bc_usrmeta_nps_topic mt
		where c.content_id=mt.usr_content_id
		  and c.is_deleted!='Y'
		".$topic_q.$brod_q.$status_q.$status_q_nle;
	$total = $db->queryOne($total_query);

	$query = "select *
		from bc_content c, bc_usrmeta_nps_topic mt
		where c.content_id=mt.usr_content_id
		  and c.is_deleted!='Y'
		".$topic_q.$brod_q.$status_q.$status_q_nle."
		order by c.content_id desc";
	$db->setLimit($limit,$start);
	$result = $db->queryAll($query);
	
	foreach($result as $key => $val)
	{
		$table = array();
		array_push($table, '<td height="20"></td>');//기본 공백

		//VNA 베트남. 승인시 체크 아이콘 추가.
		if( strstr($val['manager_status'], 'accept') )
		{
			array_push($table, '<td><img src="/led-icons/accept.png" width="16" alt="accepted" ext:qtip="Accepted" /></td>');
		}

		//VNA 베트남. 승인시 체크 아이콘 추가.
		if( strstr($val['manager_status'], 'decline') )
		{
			array_push($table, '<td><img src="/led-icons/cancel.png" width="16" alt="declined" ext:qtip="Declined" /></td>');
		}	

		if( !empty($val['attach_count']) ) //첨부파일 존재및 갯수
		{
			array_push($table, '<td><img src="/led-icons/disk3.png" alt="ATTACH" ext:qtip="Attached file count is '.$val['attach_count'].'" /></td>');
		}
		
		if(!empty($table))
		{			
			$start_table = '<table cellpadding="0" cellspacing="0" border="0"><tr>';
			$end_table ='</tr></table>';
			$result[$key]['icons'] = $start_table.join('', $table).$end_table;
		}
	}
		
	
	echo json_encode(array(
		"success" => true,
		"query" => $query,
		"data" => $result,
		"total" => $total,
		"msg" => 'Success'
	));
}
catch(Exception $e)
{
	echo json_encode(array(
		"success" => false,
		"lastquery" => $db->last_query,
		"msg" => $e->getMessage()
	));
}
?>