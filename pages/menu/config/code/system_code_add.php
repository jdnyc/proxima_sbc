<?php 
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

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
	$check = $db->queryOne("SELECT CODE FROM BC_SYS_CODE WHERE (CODE = '$code' OR CODE_NM = '$code_nm' OR CODE_NM_ENGLISH = '$code_nm_english')");
	if(!$check)
	{
		$use_yn = ($use_yn == 'on')? 'Y':'N';
		$max_code_id = $db->queryOne("select max(id) from bc_sys_code");
		$id = $max_code_id+1;
		
		$add = $db->exec("
			INSERT INTO BC_SYS_CODE
			(ID, CODE, CODE_NM, CODE_NM_ENGLISH, TYPE_ID, USE_YN, SORT, MEMO, REF1, REF2, REF3, REF4, REF5)
			VALUES 
			('".$id."', '".$code."', '".$code_nm."','".$code_nm_english."', 1, '".$use_yn."', 1, '".$memo."', '".$ref1."', '".$ref2."', '".$ref3."', '".$ref4."', '".$ref5."')
		");

		echo json_encode(array(
			'success' => true,
			'msg' => '등록완료'
		));			
	}
	else
	{
		echo json_encode(array(
			'success' => true,
			'msg' => '코드 또는 코드명이 이미 등록되어 있습니다.'
		));
	}
}
catch(Exception $e)
{	
}

?>