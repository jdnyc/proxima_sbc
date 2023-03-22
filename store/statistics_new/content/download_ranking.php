<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$s_date = $_POST['start_date'];
$e_date = $_POST['end_date'];

$limit = $_POST['limit'];
$start = $_POST['start'];

$total = $db->queryOne("select count(content_id) from bc_log where action = 'download' and created_date between ".$s_date." and ".$e_date);

$del_rank = array(
	'success' => true,
	'total' => $total,
	'down_rank' => array()
);

$db->setLimit($limit,$start);
$del_log = $mdb->queryAll("select content_id, user_id, created_date, description 
							from bc_log 
							where action = 'download'  
							and created_date between ".$s_date." and ".$e_date." 
							order by created_date desc");

$i = $start+1;
foreach($del_log as $del)
{
	$content = $mdb->queryRow("select bs_content_id, title, ud_content_id from bc_content where content_id = '{$del['content_id']}'");
	$get_type = $mdb->queryOne("select ud_content_title from bc_ud_content where ud_content_id = '{$content['ud_content_id']}'");
	
	$User_Name = $mdb->queryOne("select user_nm from bc_member where user_id = '{$del['user_id']}'");	

	array_push($del_rank['down_rank'], array('rank'=>$i, 'type'=>$get_type, 'title'=>$content['title'], 'user'=>$User_Name, 'description'=>$del['description'], 'date'=>$del['created_time']));
	
	$i++;
}

echo json_encode($del_rank);

//print_r($down_rank);
//순위 /타입  / 파일명 / 삭제자 / 삭제일
//  1   movie      2       3	   2010/02/11

?>

