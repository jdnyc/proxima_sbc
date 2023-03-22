<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$s_date = $_POST['start_date'];
$e_date = $_POST['end_date'];

$limit = $_POST['limit'];
$start = $_POST['start'];


//$total = $db->queryOne("select count(content_id) from bc_log where action = 'download' and created_date between ".$s_date." and ".$e_date);

$down_rank = array(
	'success' => true,
//	'total' => $total,
	'down_rank' => array()
);

$db->setLimit($limit,$start);
$down_log = $mdb->queryAll("select content_id, user_id, created_date, description, ud_content_id  
							from bc_log 
							where action = 'download'  
							and created_date between ".$s_date." and ".$e_date." 
							order by created_date desc");

$i = $start+1;
foreach($down_log as $down)
{
	if(!in_array($down['ud_content_id'],$CG_LIST))
	{
		continue;
	}
	$content = $mdb->queryRow("select bs_content_id, title, ud_content_id from bc_content where content_id = '{$down['content_id']}'");
	$get_type = $mdb->queryOne("select ud_content_title from bc_ud_content where ud_content_id = '{$content['ud_content_id']}'");
	
	$User_Name = $mdb->queryOne("select user_nm from bc_member where user_id = '{$down['user_id']}'");	

	array_push($down_rank['down_rank'], array('rank'=>$i, 'type'=>$get_type, 'title'=>$content['title'], 'user'=>$User_Name, 'description'=>$down['description'], 'date'=>$down['created_date']));
	
	$i++;
}

echo json_encode($down_rank);

//print_r($down_rank);
//순위 /타입  / 파일명 / 삭제자 / 삭제일
//  1   movie      2       3	   2010/02/11

?>

