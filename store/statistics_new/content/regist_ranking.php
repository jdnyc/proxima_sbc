<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
$limit = $_POST['limit'];
$start = $_POST['start'];

$s_date = $_POST['start_date'];
$e_date = $_POST['end_date'];

$mappingType = array(
	'506'	=> '동영상',
	'515'	=> '사운드',
	'518' => 	'이미지',
	'57057' => '문서'
);
// 사용자 컨텐츠 정의 목록 가져오기
$ud_content_list = $db->queryAll("select ud_content_id, ud_content_title from bc_ud_content order by show_order");
foreach ($ud_content_list as $ud_content)
{
	$mappingMetaTable[$ud_content['ud_content_id']] = $ud_content['ud_content_title'];
}

$total = $mdb->queryOne("select count(*) 
							from bc_content 
							where (status=2 or status=0) 
							and is_deleted='N'
							and created_date between ".$s_date." and ".$e_date);
$regist_rank = array(
	'success' => true,
	'total' => $total,
	'regist_rank' => array()
);

$db->setLimit($limit,$start);
$regist_log = $mdb->queryAll("select * 
								from bc_content 
								where (status='2' or status='0')
								and is_deleted='N' 
								and created_date between ".$s_date." and ".$e_date." 
								order by created_date desc ");

$i = $start+1;
foreach ($regist_log as $regist)
{
	$content = $mdb->queryRow("select * from bc_content where content_id = '{$regist['content_id']}'");

	if( $regist['user_id'] == '0' )
	{
		$user_name = 'MIGRATION';
	}
	else if( $regist['user_id'] == 'admin' )
	{
		$user_name = '자체';
	}
	else if( $regist['user_id'] == 'radio' )
	{
		$user_name = '라디오주조';
	}
	else if( $regist['user_id'] == 'satillite_channel' )
	{
		$user_name = '위성멀티주조';
	}
	else if( $regist['user_id'] == 'ground_channel' )
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

