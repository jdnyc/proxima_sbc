<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

try
{
	$limit = $_POST['limit'];
	$limit2 = $_POST['limit2'];
	$cache_max = $_POST['cache_max'];

	if($limit != '')
	{
		$limit = preg_replace("/[^0-9]/","",$limit);

		if( !is_numeric($limit) )
			throw new Exception('올바른 값이 아닙니다.');
		if( $limit < 70 || $limit > 90 )
			throw new Exception('설정 가능한 허용치가 아닙니다.');

		$db->exec("update storage_policy set
				limitspace='".$limit."'
			where id='".STORAGE_ARCHIVE_R."'");
	}
	
	if($limit2 != '')
	{
		$limit2 = preg_replace("/[^0-9]/","",$limit2);

		if( !is_numeric($limit2) )
			throw new Exception('올바른 값이 아닙니다.');
		if( $limit2 < 70 || $limit2 > 90 )
			throw new Exception('설정 가능한 허용치가 아닙니다.');

		$db->exec("update storage_policy set
				limitspace='".$limit2."'
			where id='".STORAGE_ARCHIVE_SHARE."'");
	}
	
	if($cache_max != '')
	{
		$cache_max = preg_replace("/[^0-9.]/","",$cache_max);

		if( !is_numeric($cache_max) )
			throw new Exception('올바른 값이 아닙니다.');
		if( $cache_max < 5 )
			throw new Exception('5TB 밑으로 설정할 수 없습니다.');

		$cache_max = $cache_max*1024*1024*1024*1024;

		$db->exec("update storage_policy set
				totalspace='".$cache_max."'
			where id='".STORAGE_ARCHIVE_SHARE."'");
	}

	echo json_encode(array(
		'success' => true
	));
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}
?>