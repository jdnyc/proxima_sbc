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
	if(!in_array($ud_content['ud_content_id'],$CG_LIST))
	{
		continue;
	}
	$mappingMetaTable[$ud_content['ud_content_id']] = $ud_content['ud_content_title'];	
}

//$total = $mdb->queryOne("select count(content_id) from bc_log where action = 'edit' and created_date between ".$s_date." and ".$e_date);
$edit_rank = array(
	'success' => true,
//	'total' => $total,
	'edit_rank' => array()
);
$db->setLimit($limit,$start);
$edit_log = $db->queryAll("select l.user_id, l.description, c.*
								from bc_content c, bc_log l
								where action = 'edit'  								
								and c.created_date between ".$s_date." and ".$e_date."  
								and c.content_id=l.content_id
								order by c.created_date desc");

$i = $start+1;
foreach($edit_log as $edit)
{
	if(!in_array($edit['ud_content_id'],$CG_LIST))
	{
		continue;
	}
	$content = $db->queryRow("select * from bc_content where content_id = '{$edit['content_id']}'");
	$user_name = $db->queryOne("select user_nm from bc_member where user_id='{$edit['user_id']}'");

	array_push($edit_rank['edit_rank'], array('rank'=>$i, 'type'=>$mappingMetaTable[$edit['ud_content_id']], 'title'=>$content['title'], 'user'=>$user_name, 'date'=>$edit['created_date'], 'description'=>$edit['description']));

$i++;
}

echo json_encode(
	$edit_rank
);

//print_r($down_rank);
//순위 /타입  / 파일명 / 수정한사람 / 수정일
//  1   movie      2       3	   2010/02/11

?>

