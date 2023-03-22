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
	//$date_q = " and c.created_time between $s_date and $e_date ";
	$date_q = " and l.created_date between $s_date and $e_date ";   //작업 등록일자 기준으로 나오도록 수정됨. 20111221 이도훈
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
	$query = "
		select
			c.*,
			l.created_date lcreated_time
		from
			bc_content c,
			bc_log l
		where
			l.user_id='$user_id'
		and c.is_deleted='N'
		and l.content_id=c.content_id
		and l.action='npstodas'
			$date_q
		order by c.created_date desc
		";

	$db->setLimit($limit,$start);

	$nps_list = $db->queryAll($query);
	if(PEAR::isError($nps_list)) throw new Exception($nps_list->getMessage());

	$total = $db->queryOne("select count(*) from ( $query ) cnt ");
	if(PEAR::isError($total)) throw new Exception($total->getMessage());


	foreach($nps_list as $k => $list)
	{
		$content_id = $list['content_id'];
		$created_time = $list['lcreated_time'];
		$dasquery = "
			select
				c.title title,
				c.content_id content_id,
				c.is_deleted is_deleted,
				c.meta_table_id meta_table_id,
				c.status status,
				c.created_time created_time,
				pronm.value pronm,
				datanm.value datanm
			from
				content c,
				( select * from meta_value v where v.meta_field_id='81851' ) pronm,
				( select * from meta_value v where v.meta_field_id='12435039' ) datanm
			where
				c.nps_content_id='$content_id'
				and pronm.content_id(+)=c.content_id
				and datanm.content_id(+)=c.content_id
			order by c.created_time desc
			";
		$das_list = $dbDas->queryRow($dasquery);
		if(PEAR::isError($das_list)) throw new Exception($das_list->getMessage());

		$gettask = $dbDas->queryAll(" select t.status , t.type from media m, task t where m.media_id=t.media_id and m.content_id='{$das_list['content_id']}'");
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

					$das_list['original'] = $val['status'];
				break;

				case '20':
				case '70':
				case '64':
					$das_list['transcoding'] = $val['status'];
				break;

				case '10':
					$das_list['catalog'] = $val['status'];
				break;

				case '61':
					$das_list['nearline'] = $val['status'];
				break;

				case 'archive':
					$das_list['archive'] = $val['status'];
				break;

			}
		}


		$nps_list[$k]['das_title'] = $das_list['title'];
		$nps_list[$k]['das_content_id'] = $das_list['content_id'];
		$nps_list[$k]['das_meta_table_id'] = $das_list['meta_table_id'];
		$nps_list[$k]['das_is_deleted'] = $das_list['is_deleted'];
		$nps_list[$k]['das_pronm'] = $das_list['pronm'];
		$nps_list[$k]['das_datanm'] = $das_list['datanm'];
		$nps_list[$k]['das_status'] = $das_list['status'];
		$nps_list[$k]['archive'] = $das_list['archive'];
		$nps_list[$k]['original'] = $das_list['original'];
		$nps_list[$k]['transcoding'] = $das_list['transcoding'];
		$nps_list[$k]['catalog'] = $das_list['catalog'];
		$nps_list[$k]['das_created_time'] = $das_list['created_time'];

		unset($das_list);
	}

	echo json_encode(array(
		'success'	=> true,
		'data'		=> $nps_list,
		'total'		=> $total
	));
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage().$dbDas->last_query;
}
?>