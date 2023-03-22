<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
$limit = $_POST['limit'];
$start = $_POST['start'];

$total = $mdb->queryOne("select count(id) from log where action = 'download' group by link_table_id order by count(id) desc");
$down_rank = array(
	'success' => true,
	'total' => $total,
	'down_rank' => array()
);
$db->setLimit($limit,$start);
$down_log = $mdb->queryAll("select count(id) as count_id, link_table_id from log where action = 'download' group by link_table_id order by count(id) desc ");

$i = $start+1;
foreach($down_log as $down){
	$content = $mdb->queryRow("select content_type_id, title, created_time from content where content_id = '{$down['link_table_id']}'");
	$get_type = $mdb->queryOne("select name from content_type where content_type_id = '{$content['content_type_id']}'");

	array_push($down_rank['down_rank'], array('rank'=>$i, 'type'=>$get_type, 'title'=>$content['title'], 'hit'=>$down['count_id'], 'date'=>$content['created_time']));
	
	$i++;
}

echo json_encode(
	$down_rank
);

//print_r($down_rank);
//순위 /타입  / 파일명 / 다운로드횟수 / 생성일
//  1   movie      2       3	  2010/02/11

?>

