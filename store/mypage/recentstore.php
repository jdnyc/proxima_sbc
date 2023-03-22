<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$user_id = $_SESSION['user']['user_id'];
$limit = $_POST['limit'];
$start = $_POST['start'];
if(empty($limit)){
    $limit = 10;
}

$mappingTaskType = array(
	10 => '카탈로그',
	20 => '트랜스코더',
	30 => '구간추출',
	40 => 'AVID 트랜스코더',
	60 => '전송(FS)',
	archive => '아카이브',
	61 => '니어라인',
	70 => '오디오 트랜스코더',
	80 => '전송(FTP)',
	81 => '전송(FTP)'
);

$mappingContentsType = array(
	506 => '동영상',
	515 => '사운드',
	518 => '이미지',
	57057 => '문서'
);

$mappingTaskStatus = array(
	'complete' =>'완료',
	'error' => '실패',
	'processing' =>'작업중'
);
//현재 유저의 task 테이블의 최근작업내역 값 불러오기
try
{
	$db->setLimit($limit, $start);
	//$db->setLimit(10, 1);
	$tasks = $db->queryAll("
		select c.content_id, c.title, c.content_type_id,t.status,t.id,t.start_datetime
		from content c, media m, task t
		where c.user_id = '$user_id'
		and c.content_id=m.content_id
		and m.media_id=t.media_id
		order by t.id desc");

	$total = $db->queryOne("
		select count(source)
		from task
		where media_id in (select media_id
							from media
							where content_id in(select content_id
												from content
												where user_id = '$user_id'))");

	$data = array(
		'success'	=> true,
		'data'		=> array(),
		'total'		=> 10
	);

	foreach($tasks as $task)
	{
		array_push($data['data'], array(
													'content_id'=>$task['content_id'],
													'title'=>$task['title'],
													'contentsType'=>$mappingContentsType[$task['content_type_id']],
													'startDate' => $task['start_datetime'],
													'status'=> $mappingTaskStatus[$task['status']]));
	}

	echo json_encode($data);
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}
?>