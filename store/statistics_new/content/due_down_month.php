<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
$limit = $_POST['limit'];
$start = $_POST['start'];

$today = date('Ym');
$one_month = mktime (0,0,0,date("m")-1, date("d"), date("Y"));
$one_month = date('YmdHis', $one_month);

$total = $mdb->queryOne("select count(link_table_id) from log where action = 'download' and created_time like '$today%'");
$month_down = array(
	'success' => true,
	'total' => $total,
	'month_down' => array()
);
$db->setLimit($limit,$start);
$month_log = $mdb->queryAll("select link_table_id, user_id, created_time from log where action = 'download' and created_time like '$today%' order by created_time desc ");

$i = $start+1;
foreach($month_log as $month){
	$content = $mdb->queryRow("select content_type_id, title from content where content_id = '{$month['link_table_id']}'");
	$get_type = $mdb->queryOne("select name from content_type where content_type_id = '{$content['content_type_id']}'");
	
	array_push($month_down['month_down'], array('no'=>$i, 'type'=>$get_type, 'title'=>$content['title'], 'user'=>$month['user_id'], 'date'=>$month['created_time']));
	
$i++;
}

echo json_encode(
	$month_down
);

//print_r($down_rank);
//No /타입  / 파일명 / 등록자 / 등록일
//  1   movie      2       3	   2010/02/11

?>

