<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib.php');

$user_id = $_SESSION['user']['user_id'];
$limit =		$_POST['limit'];
$start =		$_POST['start'];
$s_date=	$_POST['start_date'];
$e_date=	$_POST['end_date'];

if(empty($start)){
    $start = 0;
}
if(empty($limit)){
    $limit = 5;
}

if( !empty($_POST['start_date']) )
{
	$date_q = " and c.created_time between $s_date and $e_date ";
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
	'archive' => '아카이브',
	61 => '니어라인',
	62 => 'NPS to DAS 전송(FS)',
	60 => '전송(FS)',
	70 => '오디오 트랜스코더',
	80 => '전송(FTP)',
	81 => '전송(FTP)'
);

$mappingTaskStatus = array(
	'complete' =>		'완료',
	'error' =>			'실패',
	'progressing' =>	'실행중'
);

//현재 유저의 task 테이블의 작업내역 값 불러오기
try
{
	$query = "select
						c.*
					from
						content c
					where
						c.user_id='$user_id'
						$date_q
					order by c.created_time desc
					";
	$db->setLimit($limit,$start);

	$tasks = $db->queryAll($query);
	if(PEAR::isError($tasks)) throw new Exception($tasks->getMessage());

	$total = $db->queryOne("select count(*) from ( $query ) cnt ");
	if(PEAR::isError($total)) throw new Exception($total->getMessage());

	foreach($tasks as $key => $task)
	{

		$gettask = $db->queryAll(" select t.status , t.type from media m, task t where m.media_id=t.media_id and m.content_id='{$task['content_id']}'");
		if(PEAR::isError($gettask)) throw new Exception($gettask->getMessage());

		foreach($gettask as $val)
		{
			switch( $val['type'] )
			{
				case '60':
				case '61':
				case '62':
				case '63':
				case '80':
				case '81':

					$tasks[$key]['original'] = $val['status'];
				break;

				case '20':
				case '70':
				case '64':
					$tasks[$key]['transcoding'] = $val['status'];
				break;

				case '10':
					$tasks[$key]['catalog'] = $val['status'];
				break;

				case '61':
					$tasks[$key]['nearline'] = $val['status'];
				break;

				case 'archive':
					$tasks[$key]['archive'] = $val['status'];
				break;

			}
		}

//
//		$tasks[$key]['original'] = $db->queryOne(" select t.status from media m, task t where m.media_id=t.media_id and (t.type='62' or t.type='63' or t.type='64' or t.type='61' or t.type='60' or t.type='80' or t.type='81' ) and m.content_id='{$task['content_id']}'");
//		$tasks[$key]['transcoding'] = $db->queryOne(" select t.status from media m, task t where m.media_id=t.media_id and (t.type='20' or t.type='70'or t.type='64') and m.content_id='{$task['content_id']}'");
//		$tasks[$key]['catalog'] = $db->queryOne(" select t.status from media m, task t where m.media_id=t.media_id and t.type='10' and m.content_id='{$task['content_id']}'");
//		$tasks[$key]['nearline'] = $db->queryOne(" select t.status from media m, task t where m.media_id=t.media_id and t.type='61' and m.content_id='{$task['content_id']}'");
//		$tasks[$key]['archive'] = $db->queryOne(" select t.status from media m, task t where m.media_id=t.media_id and t.type='archive' and m.content_id='{$task['content_id']}'");
	}

	echo json_encode(
		array(
			'success'	=> true,
			'data'		=> $tasks,
			'total'		=> $total
		)
	);
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}
?>