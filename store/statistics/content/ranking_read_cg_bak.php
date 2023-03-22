<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$limit = $_POST['limit'];
$start = $_POST['start'];

$s_date = $_POST['start_date'];
$e_date = $_POST['end_date'];

if(empty($limit)){
		$limit = 50;
	}


// 사용자 컨텐츠 정의 목록 가져오기
$ud_content_list = $db->queryAll("select ud_content_id, ud_content_title from bc_ud_content order by show_order");

foreach ($ud_content_list as $ud_content)
{
	if(!in_array($ud_content['ud_content_id'],$CG_LIST))
	{
		continue;
	}
	$mappingMetaTable[$ud_content['ud_content_id']] = $ud_content['ud_content_title'];
	$count = "select 
				*
				from bc_log 
				where action = 'read' 
				and  ud_content_id = '{$ud_content['ud_content_id']}'
				and created_date between ".$s_date." and ".$e_date."  
				 ";
	$total = $db->queryOne("select count(*) 
							from (".$count.")");
	$total_count = $total_count + $total;

}


//$total = $db->queryOne("select count(*) from (".$total_count.")");
//	print_r($total);
$read_rank = array(
	'success' => true,
	'total' => $total_count,
	'read_rank' => array()
);



//print_r($start);
$query = "select 
				*
				from bc_log 
				where action = 'read' 
				and created_date between ".$s_date." and ".$e_date."  
				 ";
$order = "order by created_date,content_id desc";

$db->setLimit($limit,$start);
$read_log = $db->queryAll($query.$order);

$i = $start+1;
foreach($read_log as $read)
{
	if(!in_array($read['ud_content_id'],$CG_LIST))
	{
		//$not_in_query = " ud_content_id in (".implode(',', $CG_LIST).")";
	//
		continue;
	}

	$content = $db->queryRow("select * from bc_content where content_id = '{$read['content_id']}'");
	$get_type = $db->queryOne("select ud_content_title from bc_ud_content where ud_content_id = '{$content['ud_content_id']}'");
	$user_name = $db->queryOne("select user_nm from bc_member where user_id='{$read['user_id']}'");
	
	array_push($read_rank['read_rank'], array('rank'=>$i, 'type'=>$get_type, 'title'=>$content['title'], 'user'=>$user_name, 'date'=>$read['created_date']));

	$i++;
}

echo json_encode(
	$read_rank
);

//print_r($down_rank);
//순위 /타입  / 파일명 / 다운로드횟수 / 생성일
//  1   movie      2       3	  2010/02/11
?>