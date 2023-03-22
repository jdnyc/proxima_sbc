<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

try
{
	if(($_POST['dept_nm']))
	{
		$dept_nm = $_POST['dept_nm'];
		$members= $mdb->queryAll("select * from member where DEPT_NM like '%".$dept_nm."%'");
	}
	else
	{
		$members= $mdb->queryAll("select * from member where DEPT_NM like '%편집%'");
	}

	$data = array(
		'success'	=> true,
		'data'		=> array()
	);
	foreach($members as $member){
		array_push($data['data'],array(
			'd'=>$member['name'],
			'v'=>$member['user_id']
		));
	}
	echo json_encode($data);
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}
?>

