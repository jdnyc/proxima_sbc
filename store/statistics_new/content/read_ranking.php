<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
$limit = $_POST['limit'];
$start = $_POST['start'];

$total = $mdb->queryOne("select count(id) from log where action = 'read' group by link_table_id order by count(id) desc");
$read_rank = array(
	'success' => true,
	'total' => $total,
	'read_rank' => array()
);
$db->setLimit($limit,$start);
$read_log = $mdb->queryAll("select count(id) as countID, link_table_id from log where action = 'read' group by link_table_id order by count(id) desc ");

$i = start+1;
foreach($read_log as $read){
	$content = $mdb->queryRow("select content_type_id, title, created_time from content where content_id = '{$read['link_table_id']}'");
	$get_type = $mdb->queryOne("select name from content_type where content_type_id = '{$content['content_type_id']}'");

	array_push($read_rank['read_rank'], array('rank'=>$i, 'type'=>$get_type, 'title'=>$content['title'], 'hit'=>$read[countid], 'date'=>$content['created_time']));
	
$i++;
}

echo json_encode(
	$read_rank
);

//print_r($read_rank);
//순위 /타입  / 파일명 / 조회수 / 생성일
//  1   movie      2       3	  2010/02/11

?>

