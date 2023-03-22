<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$mappingBreake = array(
	'H' =>		"<font color=green>".'휴직'."</font>",
	'C' =>		"<font color=blue>".'재직'."</font>",
	'T' =>		"<font color=gray>".'퇴직'."</font>",
	'K' =>		"<font color=red>".'직위해제'."</font>"
);
//현재 유저의 member_update 테이블 불러오기
try
{
	$limit = empty($_POST['limit'])? 50 : $_POST['limit'];
	$start = empty($_POST['start'])? 0 : $_POST['start'];

	$total = $db->queryOne("select count(*) from member_update ");

	$db->setLimit($limit, $start);
	$updateLists = $db->queryAll("select * from member_update order by MODIFY_DATE desc ");


	$data = array(
		'success'	=> true,
		'data'		=> $updateLists,
		'total'		=> $total
	);

	echo json_encode($data);
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}
?>