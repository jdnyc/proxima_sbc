<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$user_id = $_SESSION['user']['user_id'];
$limit =		$_POST['limit'];
$start =		$_POST['start'];
$s_date=	$_POST['start_date'];
$e_date=	$_POST['end_date'];

if(empty($limit)){
    $limit = 8;
}

$mappingContentsType = array(
	506 =>		'동영상',
	515 =>		'사운드',
	518 =>		'이미지',
	57057 =>	'문서'
);

$mappingTaskType = array(
	10 => '카탈로그',
	20 => '트랜스코더',
	30 => '구간추출',
	40 => 'AVID 트랜스코더',
	archive => '아카이브',
	61 => '니어라인',
	60 => '전송(FS)',
	70 => '오디오 트랜스코더',
	80 => '전송(FTP)',
	81 => '전송(FTP)'
);

$mappingTaskStatus = array(
	'complete' =>		'완료',
	'error' =>			'실패',
	'processing' =>	'실행중'
);

//현재 유저의 task 테이블의 작업내역 값 불러오기
try
{
	$db->setLimit($limit,$start);


		$tasks = $db->queryAll("select c.title, c.content_type_id,t.status,t.id,t.type,t.start_datetime
											from content c, media m, task t
											where c.user_id = '$user_id'
											and c.content_id=m.content_id
											and m.media_id=t.media_id
											and t.start_datetime between $s_date and $e_date");

		$total = $db->queryOne("select count(c.title)
											from content c, media m, task t
											where c.user_id = '$user_id'
											and c.content_id=m.content_id
											and m.media_id=t.media_id
											and t.start_datetime between $s_date and $e_date");

		$data = array(
			'success'	=> true,
			'data'		=> array(),
			'total'		=> $total
		);

		foreach($tasks as $task){
			array_push($data['data'],array(
										'title'	=>			$task['title'],
										'contentsType'=>$mappingContentsType[$task['content_type_id']],
										'type'	=>			$mappingTaskType[$task['type']],
										'startDate' =>		$task['start_datetime'],
										'status'=>			$mappingTaskStatus[$task['status']]));
		}

	echo json_encode($data);
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}
?>