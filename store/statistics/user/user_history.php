<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$s_date = $_POST['start_date'];
$e_date = $_POST['end_date'];
$user_id = $_POST['user_id'];
$dir = $_POST['dir'];
$sort = $_POST['sort'];
if($dir == ''){
	$dir = 'desc';
}
if($sort == ''){
	$sort = 'created_date';
}
if(empty($user_id)){
	$user_id =$_SESSION['user']['user_id'];
}
$limit = $_POST['limit'];
$start = $_POST['start'];

$today = date('Ymd');
$today = $today."240000";
$one_month = mktime (0,0,0,date("m")-1, date("d"), date("Y"));
$one_month = date('YmdHis', $one_month);

$total = $mdb->queryOne("select count(log_id) from bc_log where user_id = '$user_id' and action= 'login' and created_date between $s_date and $e_date");
$user_time = array(
	'success' => true,
	'total' => $total,
	'user_time' => array(),
);
$user_name = $mdb->queryOne("select user_nm from bc_member where user_id='$user_id'");
$db->setLimit($limit,$start);
$get_login_time = $mdb->queryAll("select created_date from bc_log where user_id = '$user_id' and action = 'login' and created_date between $s_date and $e_date order by $sort $dir");

foreach($get_login_time as $time){
	array_push($user_time['user_time'], array('user_id'=>$user_id, 'user_name'=>$user_name ,'date'=>$time['created_date']));
}


//print_r($user_time);
echo json_encode(
	$user_time
	
);
//echo $mdb->last_query;

// 유저id / 유저 이름/ 접속시간

?>