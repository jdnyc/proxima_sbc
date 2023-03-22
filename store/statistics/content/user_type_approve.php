<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

define('CONTENT_STATUS_ALL', -100);

$user_content_type = $_POST['userContentType'];
if(empty($user_content_type))
{
	die('error : Parameter(userContentType) is empty');
}
try
{
	if($user_content_type == 'all'){
		$meta_table_id_condition = "";
	}else{
		$meta_table_id_condition = " and meta_table_id = '$user_content_type'";
	}

	$arr_status_condition = array(CONTENT_STATUS_ALL => "",
								  CONTENT_STATUS_COMPLETE => " and status = '".CONTENT_STATUS_COMPLETE."'",
								  CONTENT_STATUS_REFUSE => " and status = '".CONTENT_STATUS_REFUSE."'");

	$arr_query = array();
	foreach($arr_status_condition as $status_condition){
		$tmp_query = "(select count(*) count ".
						"from content where is_deleted = '0'".$meta_table_id_condition.$status_condition.")";
		array_push($arr_query, $tmp_query);
	}

	$query = implode(' union all ', $arr_query);

	//print_r($query);


	$rows = $mdb->queryAll($query);
	$total_count = $rows[0]['count'];
	$approved_count = $rows[1]['count'];
	$refused_count = $rows[2]['count'];
	$wait_count = $total_count - ($approved_count + $refused_count);

	$Total = array('type' => '전체',  'total' => (int)$total_count, 'approve' => (int)$approved_count, 'refuse' => (int)$refused_count, 'wait' => (int)$wait_count);	

	//프로그램에 대한 카운트를 가져온다.
	$arr_query = array();
	unset($tmp_query);
	$meta_table_id_condition = " and meta_table_id = '81722'";
	foreach($arr_status_condition as $status_condition){
		$tmp_query = "(select count(*) count ".
						"from content where is_deleted = '0'".$meta_table_id_condition.$status_condition.")";
		array_push($arr_query, $tmp_query);
	}
	$query = implode(' union all ', $arr_query);

	$ProgramTotalCount = $mdb->queryAll($query);

	$total_count = $ProgramTotalCount[0]['count'];
	$approved_count = $ProgramTotalCount[1]['count'];
	$refused_count = $ProgramTotalCount[2]['count'];
	$wait_count = $total_count - ($approved_count + $refused_count);

	$Program = array('type' => 'TV방송프로그램',  'total' => (int)$total_count, 'approve' => (int)$approved_count, 'refuse' => (int)$refused_count, 'wait' => (int)$wait_count);

	//소재영상에 대한 카운트를 가져온다.
	$arr_query = array();
	unset($tmp_query);
	$meta_table_id_condition = " and meta_table_id = '81767'";
	foreach($arr_status_condition as $status_condition){
		$tmp_query = "(select count(*) count ".
						"from content where is_deleted = '0'".$meta_table_id_condition.$status_condition.")";
		array_push($arr_query, $tmp_query);
	}
	$query = implode(' union all ', $arr_query);

	$MertrialTotalCount = $mdb->queryAll($query);

	$total_count = $MertrialTotalCount[0]['count'];
	$approved_count = $MertrialTotalCount[1]['count'];
	$refused_count = $MertrialTotalCount[2]['count'];
	$wait_count = $total_count - ($approved_count + $refused_count);

	$Mertrial = array('type' => '소재영상',  'total' => (int)$total_count, 'approve' => (int)$approved_count, 'refuse' => (int)$refused_count, 'wait' => (int)$wait_count);

	//참조영상에 대한 카운트를 가져온다.
	$arr_query = array();
	unset($tmp_query);
	$meta_table_id_condition = " and meta_table_id = '81768'";
	foreach($arr_status_condition as $status_condition){
		$tmp_query = "(select count(*) count ".
						"from content where is_deleted = '0'".$meta_table_id_condition.$status_condition.")";
		array_push($arr_query, $tmp_query);
	}
	$query = implode(' union all ', $arr_query);

	$Mertrial2TotalCount = $mdb->queryAll($query);

	$total_count = $Mertrial2TotalCount[0]['count'];
	$approved_count = $Mertrial2TotalCount[1]['count'];
	$refused_count = $Mertrial2TotalCount[2]['count'];
	$wait_count = $total_count - ($approved_count + $refused_count);

	$Mertrial2 = array('type' => '참조영상',  'total' => (int)$total_count, 'approve' => (int)$approved_count, 'refuse' => (int)$refused_count, 'wait' => (int)$wait_count);

	//라디오방송에 대한 카운트를 가져온다.
	$arr_query = array();
	unset($tmp_query);
	$meta_table_id_condition = " and meta_table_id = '4023846'";
	foreach($arr_status_condition as $status_condition){
		$tmp_query = "(select count(*) count ".
						"from content where is_deleted = '0'".$meta_table_id_condition.$status_condition.")";
		array_push($arr_query, $tmp_query);
	}
	$query = implode(' union all ', $arr_query);

	$RadioTotalCount = $mdb->queryAll($query);

	$total_count = $RadioTotalCount[0]['count'];
	$approved_count = $RadioTotalCount[1]['count'];
	$refused_count = $RadioTotalCount[2]['count'];
	$wait_count = $total_count - ($approved_count + $refused_count);

	$Radio = array('type' => 'R.방송프로그램',  'total' => (int)$total_count, 'approve' => (int)$approved_count, 'refuse' => (int)$refused_count, 'wait' => (int)$wait_count);

	//음반 대한 카운트를 가져온다.
	$arr_query = array();
	unset($tmp_query);
	$meta_table_id_condition = " and meta_table_id = '81769'";
	foreach($arr_status_condition as $status_condition){
		$tmp_query = "(select count(*) count ".
						"from content where is_deleted = '0'".$meta_table_id_condition.$status_condition.")";
		array_push($arr_query, $tmp_query);
	}
	$query = implode(' union all ', $arr_query);

	$MusicTotalCount = $mdb->queryAll($query);

	$total_count = $MusicTotalCount[0]['count'];
	$approved_count = $MusicTotalCount[1]['count'];
	$refused_count = $MusicTotalCount[2]['count'];
	$wait_count = $total_count - ($approved_count + $refused_count);

	$Music = array('type' => '음반',  'total' => (int)$total_count, 'approve' => (int)$approved_count, 'refuse' => (int)$refused_count, 'wait' => (int)$wait_count);

	echo json_encode(array('success' => true,
						   'data' => array($Total, $Program, $Mertrial, $Mertrial2, $Radio, $Music )));
}
catch (Exception $e)
{
	echo 'error: '.$e->getMessage();
}
?>