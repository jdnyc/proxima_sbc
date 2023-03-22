<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/MetaData.class.php');

$list_user_id = $_SESSION['user']['user_id'];
$content_id = $_POST['content_id'];
$bs_content_id = $_POST['bs_content_id'];
$limit = $_POST['limit'];
$start = $_POST['start'];


try
{
	if( empty($list_user_id) || $list_user_id == 'temp' ) throw new Exception("Login 해주세요");
	if( empty($content_id) || empty($bs_content_id) ) throw new Exception("Param Error");
	
	$msg = '성공';
	$content_fields = MetaDataClass::getFieldValueInfo('sys', $bs_content_id , $content_id);
	$datas = array();
	foreach($content_fields as $f) {
		$datas[$f['sys_meta_field_id']] = $f['value'];
	}
	$sys_table = MetaDataClass::getTableName('sys', $bs_content_id);
	$query = "select * from ".$sys_table." where sys_content_id = $content_id";
	//$datas = $db->queryRow($query);
	
	
	echo json_encode(array(
		'success' => true,
		'msg' => $msg,
		'data' => $datas
	));
}
catch (Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

?>