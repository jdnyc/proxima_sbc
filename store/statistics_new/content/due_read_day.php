<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$limit = $_POST['limit'];
$start = $_POST['start'];
$today = date('Ymd');
$total = $mdb->queryOne("select count(link_table_id) from log where action = 'read' and created_time like '$today%'");
$day_read = array(
	'success' => true,
	'total' => $total,
	'day_read' => array()
);
$db->setLimit($limit,$start);
$read_log = $mdb->queryAll("select link_table_id, user_id, created_time from log where action = 'read' and created_time like '$today%' order by created_time desc ");

$i = $start+1;
foreach($read_log as $log){
	$content = $mdb->queryRow("select content_type_id, title from content where content_id = '{$log['link_table_id']}'");
	$get_type = $mdb->queryOne("select name from content_type where content_type_id = '{$content['content_type_id']}'");

	array_push($day_read['day_read'], array('no'=>$i, 'type'=>$get_type, 'title'=>$content['title'], 'user'=>$log['user_id'], 'date'=>$log['created_time']));
	
$i++;
}

echo json_encode(
	$day_read
);

//print_r($down_rank);
//No /타입  / 파일명 / 등록자 / 등록일
//  1   movie      2       3	   2010/02/11

?>

