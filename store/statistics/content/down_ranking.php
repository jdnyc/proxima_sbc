<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$s_date = $_POST['start_date'];
$e_date = $_POST['end_date'];

$limit = $_POST['limit'];
$start = $_POST['start'];

$ud_content_list = $db->queryAll("select ud_content_id, ud_content_title from bc_ud_content where ud_content_id>'4000300' order by show_order");

foreach ($ud_content_list as $ud_content)
{
	$mappingMetaTable[$ud_content['ud_content_id']] = $ud_content['ud_content_title'];
}

$total = $mdb->queryOne("select count(content_id) from bc_log where action = 'download' and ud_content_id>'4000300' and created_date between ".$s_date." and ".$e_date);
$down_rank = array(
	'success' => true,
	'total' => $total,
	'down_rank' => array()
);

$db->setLimit($limit,$start);
$down_log = $mdb->queryAll("select content_id, user_id, created_date from bc_log where action = 'download' and ud_content_id>'4000300' and created_date between ".$s_date." and ".$e_date." order by created_date desc");
$i = $start+1;
foreach($down_log as $down)
{
	$content = $mdb->queryRow("select bc_content_id, title from bc_content where content_id = '{$down['content_id']}' and ud_content_id>'4000300'");
	$get_type = $mdb->queryOne("select bs_content_title from bc_bs_content where bs_content_id = '{$content['bs_content_id']}' and ud_content_id>'4000300'");

	array_push($down_rank['down_rank'], array('rank'=>$i, 'type'=>$get_type, 'title'=>$content['title'], 'user'=>$down['user_id'], 'date'=>$down['created_date']));

$i++;
}

echo json_encode(
	$down_rank
);

//print_r($down_rank);
//순위 /타입  / 파일명 / 다운로드횟수 / 생성일
//  1   movie      2       3	  2010/02/11

?>

