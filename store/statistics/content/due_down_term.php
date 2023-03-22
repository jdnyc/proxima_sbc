<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$limit = $_POST['limit'];
$start = $_POST['start'];
$s_date = $_POST['start_date'];
$e_date = $_POST['end_date'];

$total = $mdb->queryOne("select count(link_table_id) from log where action = 'download' and created_time between $s_date and $e_date");
$term_down = array(
	'success' => true,
	'total' => $total,
	'term_down' => array()
);
$db->setLimit($limit,$start);
$term_log = $mdb->queryAll("select link_table_id, user_id, created_time from log where action = 'download' and created_time between $s_date and $e_date order by created_time desc");

$i = $start+1;
foreach($term_log as $term){
	$content = $mdb->queryRow("select content_type_id, title from content where content_id = '{$term['link_table_id']}'");
	$get_type = $mdb->queryOne("select name from content_type where content_type_id = '{$content['content_type_id']}'");
	
	array_push($term_down['term_down'], array('no'=>$i, 'type'=>$get_type, 'title'=>$content['title'], 'user'=>$term['user_id'], 'date'=>$term['created_time']));
	
$i++;
}

echo json_encode(
	$term_down
);

//print_r($down_rank);
//No /타입  / 파일명 / 등록자 / 등록일
//  1   movie      2       3	   2010/02/11

?>

