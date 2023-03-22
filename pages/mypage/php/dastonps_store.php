<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$user_id = $_SESSION['user']['user_id'];
$limit = $_POST['limit'];
$start = $_POST['start'];
$s_date=$_POST['start_date'];
$e_date=$_POST['end_date'];

if(empty($start)){
    $start = 0;
}
if(empty($limit)){
    $limit = 8;
}

if( !empty($_POST['start_date']) )
{
	$date_q = " and c.created_date between $s_date and $e_date ";
}


$mappingContentsType = array(
	506 => '동영상',
	515 => '사운드',
	518 => '이미지',
	57057 => '문서'
);

$mappingTaskType = array(
	10 => '카탈로그',
	20 => '트랜스코더',
	30 => '구간추출',
	40 => 'AVID 트랜스코더',
	'archive' => '아카이브',
	61 => '전송(FS)',
	62 => 'DAS to NPS 전송(FS)',
	60 => '전송(FS)',
	70 => '오디오 트랜스코더',
	80 => '전송(FTP)',
	81 => '전송(FTP)'
);
$mappingTaskStatus = array(
	'complete' =>'완료',
	'error' => '실패',
	'progressing' =>'실행중'
);
//현재 유저의 task 테이블의 작업내역 값 불러오기
try
{
	$query = "select
			c.*,
			mt.ud_content_title meta_table_name,
			dtn.task_id task_id,
			original.status original,
			transcoding.status transcoding,
			catalog.status catalog
		from
			bc_content c,
			bc_ud_content mt,
			das_to_nps dtn,
			( select m.content_id,t.status,t.type from bc_media m, bc_task t where m.media_id=t.media_id and (t.type='62' ) ) original,
			( select m.content_id,t.status,t.type from bc_media m, bc_task t where m.media_id=t.media_id and (t.type='20' or t.type='64') ) transcoding,
			( select m.content_id,t.status,t.type from bc_media m, bc_task t where m.media_id=t.media_id and t.type='10' ) catalog
		where
			c.reg_user_id='$user_id'
			and c.ud_content_id=mt.ud_content_id
			and c.das_content_id is not null
			and dtn.content_id(+)=c.content_id
			and original.content_id(+)=c.content_id
			and transcoding.content_id(+)=c.content_id
			and catalog.content_id(+)=c.content_id
			$date_q
		order by c.created_date desc
		";
	$db->setLimit($limit,$start);

	$nps_list = $db->queryAll($query);


	$total = $db->queryOne("select count(*) from ( $query ) cnt ");
	


	foreach($nps_list as $k => $list)
	{
		if( !empty($list['task_id']) )//리스토어
		{
			$taskstatus = $dbDas->queryOne("
				select
					status
				from
					task
				where
					id='{$list['task_id']}'
			");

			$nps_list[$k]['restore'] = $taskstatus;

		}
		else
		{
			$nps_list[$k]['restore'] = 'non';
		}

		$das_content_id = $list['das_content_id'];
		$media_archive = $dbDas->queryRow("
			select
				path,
				status
			from
				media
			where
				content_id='$das_content_id'
			and type='archive'
		");


		if( !empty( $media_archive) ) //니어라인 존재
		{
			$nps_list[$k]['archive'] = $media_archive['status'];
		}
		else
		{
			$nps_list[$k]['archive'] = 'X';
		}


	}

	echo json_encode(array(
		'success'	=> true,
		'data'		=> $nps_list,
		'total'		=> $total
	));
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage().$dbDas->last_query.$db->last_query;
}
?>