<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
$limit = $_POST['limit'];
$start = $_POST['start'];

$one_year = date('Y');

$total = $mdb->queryOne("select count(*) from content where created_time between ".$one_year."0000000000 and ".$one_year."1231240000 ");
$year_regist = array(
	'success' => true,
	'total' => $total,
	'year_regist' => array()
);
$db->setLimit($limit,$start);
$year_log = $mdb->queryAll("select ct.name, c.title, c.user_id, c.created_time from content c, content_type ct where c.created_time between ".$one_year."0000000000 and ".$one_year."1231240000 and ct.content_type_id = c.content_type_id order by c.created_time desc");

$i = $start+1;
foreach($year_log as $year)
{	
	array_push($year_regist['year_regist'], array('no'=>$i, 'type'=>$year['name'], 'title'=>$year['title'], 'user'=>$year['user_id'], 'date'=>$year['created_time']));
	
	$i++;
}

echo json_encode(
	$year_regist
);

//print_r($down_rank);
//No /타입  / 파일명 / 등록자 / 등록일
//  1   movie      2       3	   2010/02/11

?>

