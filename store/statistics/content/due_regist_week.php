<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
$limit = $_POST['limit'];
$start = $_POST['start'];

$today = date('YmdHis');
$last_week = mktime (0,0,0,date("m"), date("d")-7, date("Y"));
$last_week = date('YmdHis', $last_week);
//$last_week = "20100625000000";
$total = $mdb->queryOne("select count(*) from content c, content_type ct where c.created_time between $last_week and $today and ct.content_type_id = c.content_type_id");
$week_regist = array(
	'success' => true,
	'week' => $last_week,
	'total' => $total,
	'week_regist' => array()
);
$db->setLimit($limit,$start);
$week_log = $mdb->queryAll("select ct.name, c.title, c.user_id, c.created_time from content c, content_type ct where c.created_time between $last_week and $today and ct.content_type_id = c.content_type_id order by c.created_time desc");

$i = $start+1;
foreach($week_log as $week)
{
	array_push($week_regist['week_regist'], array('no'=>$i, 'type'=>$week['name'], 'title'=>$week['title'], 'user'=>$week['user_id'], 'date'=>$week['created_time']));
	
	$i++;
}


echo json_encode(
	$week_regist	
);

//print_r($down_rank);
//No /타입  / 파일명 / 등록자 / 등록일
//  1   movie      2       3	   2010/02/11

?>

