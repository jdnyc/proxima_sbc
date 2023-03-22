<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$limit = $_POST['limit'];
$start = $_POST['start'];

$s_date = $_POST['start_date'];
$e_date = $_POST['end_date'];


// 사용자 컨텐츠 정의 목록 가져오기
$ud_content_list = $db->queryAll("select ud_content_id, ud_content_title from bc_ud_content order by show_order");
foreach ($ud_content_list as $ud_content)
{
	$mappingMetaTable[$ud_content['ud_content_id']] = $ud_content['ud_content_title'];
}
$total = $mdb->queryOne("select count(log_id) from bc_log where action = 'read' and created_date between ".$s_date." and ".$e_date);
$read_rank = array(
	'success' => true,
	'total' => $total,
	'read_rank' => array()
);
$db->setLimit($limit,$start);
$read_log = $mdb->queryAll("select * from bc_log where action = 'read' and created_date between ".$s_date." and ".$e_date."  order by created_date desc ");

//$read_log = $mdb->queryAll($query);

$i = $start+1;
foreach($read_log as $read)
{
	$content = $mdb->queryRow("select * from bc_content where content_id = '{$read['content_id']}'");
	$user_name = $db->queryOne("select user_nm from bc_member where user_id='{$read['user_id']}'");
	if ( empty($user_name) )
	{
		$user_name = $read['user_id'];
	}
	array_push($read_rank['read_rank'], array('rank'=>$i, 'type'=>$mappingMetaTable[$read['ud_content_id']], 'title'=>$content['title'], 'user'=>$user_name, 'date'=>$read['created_date']));

	$i++;
}

echo json_encode(
	$read_rank
);

//print_r($down_rank);
//순위 /타입  / 파일명 / 다운로드횟수 / 생성일
//  1   movie      2       3	  2010/02/11

?>

