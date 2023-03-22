<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');


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
		'space' => 'space',
		'count' => 'count'
	);

$j = 0;
for($i = 1; $i < 13; $i++){
	$month = str_pad($i, 2, "0", STR_PAD_LEFT);

	$query = "select sum(filesize) as space, count(*) as count  from bc_media where (media_type='original' or media_type='proxy' or media_type='ktds_web')and created_date between ".$year.$month."00000000 and ".$year.$month."31240000 and status is null and filesize is not null";
	$each_kind = $mdb->queryRow($query);

	$query = "select count(*) as count  from bc_media where media_type='original' and created_date between ".$year.$month."00000000 and ".$year.$month."31240000  and status is null and filesize is not null";
	$count = $mdb->queryOne($query);


	$query = "select sum(filesize) as space, count(*) as count  from bc_media where (media_type='original' or media_type='proxy' or media_type='ktds_web')and created_date between ".$year.$month."00000000 and ".$year.$month."31240000 and filesize is not null";
	$sum_each_kind = $mdb->queryRow($query);

	$query = "select count(*) as count  from bc_media where media_type='original' and created_date between ".$year.$month."00000000 and ".$year.$month."31240000  and filesize is not null";
	$sum_count = $mdb->queryOne($query);

	//echo $query;

	array_push($task_list['task'], array(
		'month' => $month.' '._text('MN00221'),
		'space' => formatBytes($each_kind['space']),
		'count' => $count,//(int)$each_kind['count'] / 3
		'sum_space' => formatBytes($sum_each_kind['space']),
		'sum_count' => $sum_count//(int)$each_kind['count'] / 3
		));//!!'월'

	/*foreach($each_kind as $kind){
		$task_list['task'][$j][$kind['action']] = $kind['total_count'];
	}*/
	$j++;


}

//print_r($task_list);
echo json_encode(
	$task_list
);
//echo $mdb->last_query;

?>