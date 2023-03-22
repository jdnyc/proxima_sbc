<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
$limit = $_POST['limit'];
$start = $_POST['start'];

$today = date('Ym');
$one_month = mktime (0,0,0,date("m")-1, date("d"), date("Y"));
$one_month = date('YmdHis', $one_month);

$total = $mdb->queryOne("select count(*) from content where created_time like '$today%'");
$month_regist = array(
	'success' => true,
	'total' => $total,
	'month_regist' => array()
);


$db->setLimit($limit,$start);
$month_log = $mdb->queryAll("select ct.name, c.title, c.user_id, c.created_time from content c, content_type ct where c.created_time like '$today%' and ct.content_type_id = c.content_type_id order by c.created_time desc");

$i = $start+1;
foreach($month_log as $month){
		
	array_push($month_regist['month_regist'], array('no'=>$i, 'type'=>$month['name'], 'title'=>$month['title'], 'user'=>$month['user_id'], 'date'=>$month['created_time']));
	
$i++;
}

echo json_encode(
	$month_regist
);

//print_r($down_rank);
//No /타입  / 파일명 / 등록자 / 등록일
//  1   movie      2       3	   2010/02/11

?>

