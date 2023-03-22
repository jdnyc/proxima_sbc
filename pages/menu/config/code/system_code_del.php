<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
$id = $_REQUEST['id'];

try
{
	if($id)
	{
		$delete = $db->exec("delete from bc_sys_code where id=$id");
		echo json_encode(array(
			'success' => true,
			'msg' => '삭제완료'
		));
	}
	else
	{
		echo json_encode(array(
			'success' => false,
			'msg' => '정보가 제대로 전달되지 않았습니다.'
		));
	}
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => "에러: $e"
	));
}


?>