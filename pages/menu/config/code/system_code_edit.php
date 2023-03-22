<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$id = $_REQUEST['id'];
$code = $_REQUEST['code'];
$code_nm = $_REQUEST['code_nm'];
$code_nm_english = $_REQUEST['code_nm_english'];
$use_yn = $_REQUEST['use_yn'];
$memo = $_REQUEST['memo'];
$ref1 = $_REQUEST['ref1'];
$ref2 = $_REQUEST['ref2'];
$ref3 = $_REQUEST['ref3'];
$ref4 = $_REQUEST['ref4'];
$ref5 = $_REQUEST['ref5'];

try
{
	$use_yn = ($use_yn == 'on')? 'Y':'N';
	$edit = $db->exec("
		update	bc_sys_code
		set	code_nm ='$code_nm'
				, code_nm_english ='$code_nm_english'
				, use_yn ='$use_yn'
				, memo = '$memo'
				, ref1 = '$ref1'
				, ref2 = '$ref2'
				, ref3 = '$ref3'
				, ref4 = '$ref4'
				, ref5 = '$ref5'
		where id = '$id'"
	);
	echo json_encode(array(
		'success' => true,
		'msg' => '수정 완료'
	));
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => "에러: $e"
	));
}


?>