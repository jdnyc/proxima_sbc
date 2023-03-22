<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

try
{

	$CodeInfoList = getCodeInfo('MEDCD');

	array_unshift($CodeInfoList , array(
		'name'=>'전체',
		'code'=>'all'
	));

	echo json_encode(array(
		'success' => true,
		'data' => $CodeInfoList,
		'total'=> $total
	));
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage().$db_ms->lasy_query;
}


?>