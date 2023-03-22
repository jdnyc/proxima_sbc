<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

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
	'archive' => '아카이브',
	61 => '니어라인',
	62 => 'DAS to NPS 전송(FS)',
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
	'progressing' =>'실행중'
);
//현재 유저의 task 테이블의 최근작업내역 값 불러오기
try
{
	$category_id = $db->queryOne("select category_id from user_mapping where user_id='$user_id'");
	if(PEAR::isError($category_id)) throw new Exception($category_id->getMessage());
	if( !empty($category_id) )
	{
		$where = " c.category_id='$category_id' and ";
	}

	$query = "select
				c.*,
				original.status original,
				transcoding.status transcoding,
				catalog.status catalog
			from
				bc_content c,
				( select m.content_id,t.status,t.type from bc_media m, bc_task t where m.media_id=t.media_id and (t.type='62' or t.type='61' or t.type='80' or t.type='60' ) ) original,
				( select m.content_id,t.status,t.type from bc_media m, bc_task t where m.media_id=t.media_id and (t.type='20' or t.type='64') ) transcoding,
				( select m.content_id,t.status,t.type from bc_media m, bc_task t where m.media_id=t.media_id and t.type='10' ) catalog
			where
				".$where."
				 original.content_id(+)=c.content_id
				and transcoding.content_id(+)=c.content_id
				and catalog.content_id(+)=c.content_id
			order by c.created_date desc
			";

	$db->setLimit($limit,$start);

	$tasks = $db->queryAll($query);
	if(PEAR::isError($tasks)) throw new Exception($tasks->getMessage());

	$total = $db->queryOne("select count(*) from ( $query ) cnt ");
	if(PEAR::isError($total)) throw new Exception($total->getMessage());

	foreach($tasks as $key => $task)
	{


		$gettask = $db->queryAll(" select t.status , t.type from bc_media m, bc_task t where m.media_id=t.media_id and m.content_id='{$task['content_id']}'");
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

			}
		}
	}



	$data = array(
		'success'	=> true,
		'data'		=> $tasks,
		'total'		=> $total
	);

	echo json_encode($data);
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}
?>