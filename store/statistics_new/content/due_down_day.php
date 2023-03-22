<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
$limit = $_POST['limit'];
$start = $_POST['start'];

$today = date('Ymd');

$total = $mdb->queryOne("select count(link_table_id) from log where action = 'download' and created_time like '$today%'");
$day_down = array(
	'success' => true,
	'total' => $total,
	'day_down' => array()
);
$db->setLimit($limit,$start);
$down_log = $mdb->queryAll("select link_table_id, user_id, created_time from log where action = 'download' and created_time like '$today%' order by created_time desc");
$i = $start+1;
foreach($down_log as $log){
	$content = $mdb->queryRow("select content_type_id, title from content where content_id = '{$log['link_table_id']}'");
	$get_type = $mdb->queryOne("select name from content_type where content_type_id = '{$content['content_type_id']}'");

	array_push($day_down['day_down'], array('no'=>$i, 'type'=>$get_type, 'title'=>$content['title'], 'user'=>$log['user_id'], 'date'=>$log['created_time']));
	
$i++;
}

echo json_encode(
	$day_down
);

//print_r($down_rank);
//No /타입  / 파일명 / 등록자 / 등록일
//  1   movie      2       3	   2010/02/11

?>

