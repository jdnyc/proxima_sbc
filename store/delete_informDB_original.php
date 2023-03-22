<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$limit =		$_POST['limit'];
$start =		$_POST['start'];
$s_date =		$_POST['start_date'];
$e_date =		$_POST['end_date'];
$index =		$_POST['index'];
$search_val	=$_POST['search_val'];

if(empty($limit)){
    $limit = 100;
}

$mappingMediaType = array(
	original => "<font color=green>".'On-line'."</font>",
	nearline => "<font color=green>".'Near-line'."</font>"
);

$mappingDeleteFlag = array(
	//NULL =>	"<font color=gray>".'니어라인에 존재하지 않음'."<font color=red>".'(삭제대상 아님)',
	"(null)"=>	"<font color=gray>".'삭제된 파일입니다.'."</font>",
	0 =>		"<font color=red>".'삭제 실패'."</font>",
	1 =>		"<font color=blue>".'삭제 성공'."</font>",
	2 =>		"<font color=red>".'삭제실패'."</font>",
	3 =>		"<font color=blue>".'DB삭제 성공'."</font>",
	5 =>		"<font color=green>".'복구된 파일입니다.'."</font>"
);

$mappingMetaTable = array(
	81722 =>	'TV 방송프로그램',
	81767 =>	'소재영상',
	81768 =>	'참조영상'
);

//현재 유저의 content 테이블 불러오기
try
{
	if($index=='1')
	{
		$seachDate="and m.delete_date between '$s_date' and '$e_date'";
	}
	else
	{
		$seachDate="";
	}

	if($search_val=='삭제성공')
	{
		$del_flag="and (m.flag='1' or m.flag='3')";
	}

	if($search_val=='삭제실패')
	{
		$del_flag="and (m.flag='0' or m.flag='2')";
	}

	$db->setLimit($limit,$start);

	$deletehistorys = "select  c.title, c.bs_content_id,
										c.content_id, m.created_date,
										m.status, m.media_type, 
										m.deleted_date, m.flag, m.path, c.ud_content_id

									from bc_media m, bc_content c

									where  c.content_id=m.content_id
									and m.media_type='original'
									
									".$del_flag."
									and c.ud_content_id='506'".
									$seachDate."order by m.created_date desc";
	$results = $db->queryAll($deletehistorys);

	$total = $db->queryOne("select  count(*)

							from bc_media m, bc_content c

							where  c.content_id=m.content_id
									and m.media_type='original'
									".$del_flag."
									and c.ud_content_id='506'".
									$seachDate);

	$data = array(
		'success'	=> true,
		'data'		=> array(),
		'total'		=> $total
	);

	$total = 1;
	foreach($results as $result){
		array_push($data['data'],array(
			'id'=>				$result['content_id'],
			'title'	=>			$result['title'],
			'contentsID'=>		$result['content_id'],
			'path'		=>		"<font color=green>".$result['path'],
			'mediaType' =>		$mappingMediaType[$result['media_type']],
			'contentsType'=>	$mappingMetaTable[$result['ud_content_id']],
			'created_time'=>	$result['created_date'],
			'delete_date'=>		$result['deleted_date'],
			'delete_result'=>	$mappingDeleteFlag[$result['flag']],
			'IsDeleted' =>		$result['status'],
			// delete_status필드가 없어지고 status로 대체
			'status'	=>		$mappingDeleteFlag[$result['status']]
		));
	}

	echo json_encode($data);
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}
?>