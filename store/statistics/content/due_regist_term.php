<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$s_date = $_POST['start_date'];
$e_date = $_POST['end_date'];
$limit = $_POST['limit'];
$start = $_POST['start'];

$total = $mdb->queryOne("select count(content_type_id) from content where created_time between $s_date and $e_date");
$term_regist = array(
	'success' => true,
	'total' => $total,
	'term_regist' => array()
);
$db->setLimit($limit,$start);
$term_log = $mdb->queryAll("select content_type_id, title, user_id, created_time from content where created_time between $s_date and $e_date order by created_time desc");

$i = $start+1;
foreach ($term_log as $term)
{
	$get_type = $mdb->queryOne("select name from content_type where content_type_id = '{$term['content_type_id']}'");

	array_push($term_regist['term_regist'], array('no'=>$i, 'type'=>$get_type, 'title'=>$term['title'], 'user'=>$term['user_id'], 'date'=>$term['created_time']));
	
	$i++;
}

echo json_encode(
	$term_regist
);

//print_r($down_rank);
//No /타입  / 파일명 / 등록자 / 등록일
//  1   movie      2       3	   2010/02/11

?>

