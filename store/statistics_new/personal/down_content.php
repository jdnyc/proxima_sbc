<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$s_date = $_POST['start_date'];
$e_date = $_POST['end_date'];
$user_id = $_POST['user_id'];
if(empty($user_id)){
	$user_id =$_SESSION['user']['user_id'];
}
$limit = $_POST['limit'];
$start = $_POST['start'];

//사용자별 다운로드 리스트
$total = $mdb->queryOne("select count(link_table_id) from log where user_id = '$user_id' and action = 'download' and created_time between $s_date and $e_date");
$down_list = array(
	'success' => true,
	'total' => $total,
	'down' => array()
);
$db->setLimit($limit,$start);
$query = "select link_table_id, created_time from log where user_id = '$user_id' and action = 'download' and created_time between $s_date and $e_date";
$datas = $mdb->queryAll($query);
foreach($datas as $data){
	$get_title = $mdb->queryAll("select ct.name, c.title from content c, content_type ct where c.content_id = '{$data['link_table_id']}' and c.content_Type_id = ct.content_Type_id");

	foreach($get_title as $titles){
		array_push($down_list['down'], array('content'=>$titles['title'], 'type'=>$titles['name'], 'date'=>$data['created_time']));
	}
}

echo json_encode(
	$down_list
);

//print_r($read_list);
?>