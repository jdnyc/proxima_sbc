<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

try
{
	$members= $mdb->queryAll("select distinct dept_nm from member");
	$data = array(
		'success'	=> true,
		'data'		=> array()
	);
	foreach($members as $member){
		array_push($data['data'],array(
			'd'=>$member['dept_nm'],
			'v'=>$member['dept_nm']
		));
	}
	echo json_encode($data);
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}
?>

