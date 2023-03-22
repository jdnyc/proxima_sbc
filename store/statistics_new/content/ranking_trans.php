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
$mappingMetaTable = array(
	'81722'	=>	'TV 방송 프로그램',
	'81767'	=>	'소재영상',
	'81768'	=>	'참조 영상',
	'81769'	=>	'음원',
	'4023846'	=>	'R.방송프로그램',
);
$total = $mdb->queryOne("select count(id) from log where action = 'dastonps' and created_time between ".$s_date." and ".$e_date);
$read_rank = array(
	'success' => true,
	'total' => $total,
	'read_rank' => array()
);
$db->setLimit($limit,$start);
$read_log = $mdb->queryAll("select link_table_id, user_id, created_time, description from log where action = 'dastonps' and created_time between ".$s_date." and ".$e_date."  order by created_time desc ");

$i = $start+1;
foreach($read_log as $read)
{
	$content = $mdb->queryRow("select * from content where content_id = '{$read['link_table_id']}'");
	$user_name = $db->queryOne("select name from member where user_id='".$read['user_id']."'");

	if ( empty($user_name) ){
		$user_name = $read['user_id'];
	}
	array_push($read_rank['read_rank'],
		array(
			'rank'=>$i,
			'type'=>$mappingMetaTable[$content['meta_table_id']],
			'title'=>$content['title'],
			'user'=>$user_name,
			'date'=>$read['created_time'],
			'description'=> $read['description']
		)
	);

	$i++;
}

echo json_encode(
	$read_rank
);

//print_r($down_rank);
//순위 /타입  / 파일명 / 다운로드횟수 / 생성일
//  1   movie      2       3	  2010/02/11

?>

