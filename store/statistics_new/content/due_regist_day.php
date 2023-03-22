<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
$limit = $_POST['limit'];
if(empty($limit)){
	$limit = "50";
}
$start = $_POST['start'];
if(empty($start)){
	$start = "0";
}

$today = date('Ymd');
$total = $mdb->queryOne("select count(*) from content where created_time like '$today%'");
$day_regist = array(
	'success' => true,
	'total' => $total,
	'day_regist' => array()
);
$db->setLimit($limit,$start);
$day_log = $mdb->queryAll("select ct.name, c.title, c.user_id, c.created_time from content c, content_type ct where c.created_time like '$today%' group by ct.name, c.title, c.user_id, c.created_time order by c.created_time desc");

$i = $start+1;
foreach($day_log as $day){

	array_push($day_regist['day_regist'], array('no'=>$i, 'type'=>$day['name'], 'title'=>$day['title'], 'user'=>$day['user_id'], 'date'=>$day['created_time']));
	
$i++;
}

echo json_encode(
	$day_regist
);


//print_r($down_rank);
//No /타입  / 파일명 / 등록자 / 등록일
//  1   movie      2       3	   2010/02/11

?>

