<?php
/*
//11-11-24, 승수.
//코드id만 넘어온 경우와 코드유형id가 넘어온 경우 구분.
//코드 유형을 삭제시에는 먼저 하위코드 갯수가 몇갠지 알려주고,
//그래도 삭제하겠다고 선택하면 $del_continue값이 넘어와서 진짜 삭제를 하게된다.


2016-04-01
code, code type 삭제
코드 유형은 코드유형에 포함된 코드 모두 함께 삭제
*/
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$code_id = $_REQUEST['code_id'];

try
{
	$type = $_POST['type'];
	switch($type){
		case 'code_type':
			$query_code = "
				DELETE	FROM	 BC_CODE
				WHERE	CODE_TYPE_ID = ".$code_id."
			";
			$query_type = "
				DELETE	FROM	 BC_CODE_TYPE
				WHERE	ID = ".$code_id."
			";
			$db->exec($query_code);
			$db->exec($query_type);
		break;
		case 'code':
			$query_code = "
				DELETE	FROM	 BC_CODE
				WHERE	ID = ".$code_id."
			";
			$db->exec($query_code);
		break;
	}
	echo json_encode(array(
		'success' => true,
		'query_type' => $query_type,
		'query_code' => $query_code
	));
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => _text('MN00022')." : ".$e
	));
}


?>