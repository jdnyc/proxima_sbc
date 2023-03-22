<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$year = "2010";
$s_month = "02";
$e_month = "02";
$user = "jbkim";

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

	$query = "select count(id) as total_count, action from log where user_id = '$user' and created_time between ".$year.$month."00000000 and ".$year.$month."31240000 group by action";
	$each_kind = $mdb->queryAll($query);

	array_push($task_list['task'], array('month' => $month));

	foreach($each_kind as $kind){
		$task_list['task'][$j][$kind['action']] = $kind['total_count'];
	}
	$j++;


}

//print_r($task_list);
echo json_encode(
	$task_list
);


?>