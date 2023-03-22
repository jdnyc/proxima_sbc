<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
$limit = $_POST['limit'];
$start = $_POST['start'];

$s_date = $_POST['start_date'];
$e_date = $_POST['end_date'];


// 사용자 컨텐츠 정의 목록 가져오기
//$ud_content_list = $db->queryAll("select ud_content_id, ud_content_title from bc_ud_content where ud_content_id>'4000300' order by show_order");
$ud_content_list = $db->queryAll("select ud_content_id, ud_content_title from bc_ud_content");
foreach ($ud_content_list as $ud_content)
{
	if( !in_array( $ud_content['ud_content_id'], $CG_LIST  ) )
	{
		continue;
	}
	$mappingMetaTable[$ud_content['ud_content_id']] = $ud_content['ud_content_title'];
}

$regist_rank = array(
	'success' => true,
	'regist_rank' => array()
);

$db->setLimit($limit,$start);
/*
$regist_log = $mdb->queryAll("select * 
								from bc_content 
								where (status='2' or status='0')
								and is_deleted='N' 
								and ud_content_id>'4000300' 
								and created_date between ".$s_date." and ".$e_date." 
								order by created_date desc ");
*/
$regist_log = $mdb->queryAll("select l.user_id, c.*
								from bc_content c, bc_log l
								where (c.status='2' or c.status='0')
								and c.is_deleted='N' 
								and l.action='regist' 
								and c.created_date between ".$s_date." and ".$e_date."  
								and c.content_id=l.content_id
								order by c.created_date desc ");
$i = $start+1;
foreach ($regist_log as $regist)
{
	if( !in_array( $regist['ud_content_id'], $CG_LIST  ) )
	{
		continue;
	}
	$content = $mdb->queryRow("select * from bc_log l,bc_content c where l.content_id = '{$regist['content_id']}' and l.content_id=c.content_id");

	if( $regist['l.user_id'] == '0' )
	{
		$user_name = 'MIGRATION';
	}
	else if( $regist['l.user_id'] == 'admin' )
	{
		$user_name = '자체';
	}
	else if( $regist['l.user_id'] == 'radio' )
	{
		$user_name = '라디오주조';
	}
	else if( $regist['l.user_id'] == 'satillite_channel' )
	{
		$user_name = '위성멀티주조';
	}
	else if( $regist['l.user_id'] == 'ground_channel' )
	{
		$user_name = '지상파주조';
	}
	else
	{
		$user_name = $db->queryOne("select user_nm from bc_member where user_id='{$content['user_id']}'");
	}

	array_push($regist_rank['regist_rank'], array('rank'=>$i, 'type'=>$mappingMetaTable[$content['ud_content_id']], 'title'=>$content['title'], 'user'=>$user_name, 'date'=>$content['created_date']));

	$i++;
}

echo json_encode($regist_rank);

//print_r($down_rank);
//순위 /타입  / 파일명 / 등록자 / 생성일
//  1   movie      2       admin	  2010/02/11

?>

