<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');


$year = $_POST['year'];
if(empty($year))
{
	$year = date('Y');
}

$user_id = $_POST['user_id'];
if(empty($user_id))
{
	$user_id =$_SESSION['user']['user_id'];
}
//사용자별 작업 통계

$task_list = array(
	'success' => true,
	'task' => array()
);

$cc = array(
		'catalog' => 'catalog',
		'delete' => 'delete',
		'download' => 'download',
		'edit' => 'edit',
		'login' => 'login',
		'read' => 'read',
		'regist' => 'regist',
		'trans' => 'trans',
		'transfer' => 'transfer'
	);

$j = 0;
for($i = 1; $i < 13; $i++){
	$month = str_pad($i, 2, "0", STR_PAD_LEFT);

	$query = "select count(log_id) as total_count, action from bc_log where user_id = '$user_id' and created_date between ".$year.$month."00000000 and ".$year.$month."31240000 group by action";
	$each_kind = $mdb->queryAll($query);

	array_push($task_list['task'], array('month' => $month.' '._text('MN00221')));//!!'월'

	foreach($each_kind as $kind){
		$task_list['task'][$j][$kind['action']] = $kind['total_count'];
	}
	$j++;


}

//print_r($task_list);
echo json_encode(
	$task_list
);
//echo $mdb->last_query;

?>