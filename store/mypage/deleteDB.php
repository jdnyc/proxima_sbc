<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$limit =		$_POST['limit'];
$start =		$_POST['start'];
$s_date =	$_POST['start_date'];
$e_date =	$_POST['end_date'];

if(empty($limit)){
    $limit = 10;
}

$mappingMediaType = array(
	original => "<font color=green>".'On-line'."</font>",
	nearline => "<font color=green>".'Near-line'."</font>"
);

$mappingDeleteFlag = array(
	0 =>		"<font color=red>".'삭제 실패'."</font>",
	1 =>		"<font color=blue>".'삭제 성공'."</font>",
	2 =>		"<font color=red>".'삭제실패'."</font>",
	3=>		"<font color=green>".'DB만 삭제성공'."</font>"
);

$mappingContentsType = array(
	506 =>		'동영상',
	515 =>		'사운드',
	518 =>		'이미지',
	57057 =>	'문서'
);

//현재 유저의 content 테이블 불러오기
try
{
	$db->setLimit($limit,$start);

	$deletehistorys = $db->queryAll("select flag,path,media_id,created_time,
															delete_status,delete_date,type
													from media
													where (flag='2' or flag='1' or flag='3')
													and delete_date between $s_date and $e_date
													");

	$total = $db->queryOne("select count(*)
										from media
										where (flag='2' or flag='1' or flag='3')
										and delete_date between $s_date and $e_date
										");
	$data = array(
		'success'	=> true,
		'data'		=> array(),
		'total'		=> $total
	);

	$total = 1;
	foreach($deletehistorys as $deletehistory){
		array_push($data['data'],array(

		'id' =>						$deletehistory['content_id'],
		'type'=>						$mappingMediaType[$deletehistory['type']],
		'path'	=>					$deletehistory['path'],
		'mediaid' =>				$deletehistory['media_id'],
		'created_date'=>			$deletehistory['created_time'],
		'delete_date'=>			$deletehistory['delete_date'],
		'delete_result'=>			$mappingDeleteFlag[$deletehistory['flag']],
		'status'	=>					"<font color=red>".$deletehistory['delete_status']."</font>"
		));
		}

	echo json_encode($data);
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}
?>