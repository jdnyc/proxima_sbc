<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$limit = $_POST['limit'];
$start = $_POST['start'];
$one_year = date('Y');

$total = $mdb->queryOne("select count(link_table_id) from log where action = 'read' and created_time between ".$one_year."0000000000 and ".$one_year."1231240000");
$year_read = array(
	'success' => true,
	'total' => $total,
	'year_read' => array()
);
$db->setLimit($limit,$start);
$year_log = $mdb->queryAll("select link_table_id, user_id, created_time from log where action = 'read' and created_time between ".$one_year."0000000000 and ".$one_year."1231240000 order by created_time desc");

$i = $start+1;
foreach($year_log as $year){
	$content = $mdb->queryRow("select content_type_id, title from content where content_id = '{$year['link_table_id']}'");
	$get_type = $mdb->queryOne("select name from content_type where content_type_id = '{$content['content_type_id']}'");
	
	array_push($year_read['year_read'], array('no'=>$i, 'type'=>$get_type, 'title'=>$content['title'], 'user'=>$year['user_id'], 'date'=>$year['created_time']));
	
$i++;
}

echo json_encode(
	$year_read
);

//print_r($down_rank);
//No /타입  / 파일명 / 등록자 / 등록일
//  1   movie      2       3	   2010/02/11

?>

