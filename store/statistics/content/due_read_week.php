<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$limit = $_POST['limit'];
$start = $_POST['start'];
$today = date('YmdHis');
$last_week = mktime (0,0,0,date("m"), date("d")-7, date("Y"));
$last_week = date('YmdHis', $last_week);

$total = $mdb->queryOne("select count(link_table_id) from log where action = 'read' and created_time between $last_week and $today ");
$week_read = array(
	'success' => true,
	'total' => $total,
	'week_read' => array()
);
$db->setLimit($limit,$start);
$week_log = $mdb->queryAll("select link_table_id, user_id, created_time from log where action = 'read' and created_time between $last_week and $today order by created_time desc");

$i = $start+1;
foreach($week_log as $week){
	$content = $mdb->queryRow("select content_type_id, title from content where content_id = '{$week['link_table_id']}'");
	$get_type = $mdb->queryOne("select name from content_type where content_type_id = '{$content['content_type_id']}'");
	
	array_push($week_read['week_read'], array('no'=>$i, 'type'=>$get_type, 'title'=>$content['title'], 'user'=>$week['user_id'], 'date'=>$week['created_time']));
	
$i++;
}

echo json_encode(
	$week_read
);

//print_r($week_read);
//No /타입  / 파일명 / 등록자 / 등록일
//  1   movie      2       3	   2010/02/11

?>

