<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/SGL.class.php');

try{
	$sgl = new SGL();

	$rtn = $sgl->FlashNetListGroup('');

	if(!$rtn['success']) {
		throw new Exception ($return['msg']);
	}

	$groups = $rtn['groups'];
	$count = $groups['GroupCount.DWD'];
	//SGL그룹 코드타입 ID 조회
	$code_type_id = $db->queryOne("
						SELECT ID
						FROM BC_CODE_TYPE
						WHERE CODE = 'sgl_group_list'
					");
// 기존등록된 그룹은 삭제
	$exist_group = $db->exec("
						DELETE FROM BC_CODE
						WHERE CODE_TYPE_ID = '$code_type_id'
					");
// 동기화시 그룹은 신규추가
	for($i=0; $i < $count; $i++) {

		$group_name = $groups->Group[$i][GroupName];
		$seq_bc_code = getSequence('seq_bc_code_id');

		$db->exec("
			INSERT INTO BC_CODE
				(ID, CODE, NAME, CODE_TYPE_ID)
			VALUES
				(".$seq_bc_code.", '$group_name', '$group_name', '$code_type_id')
		");
	}

	echo json_encode(array(
			'success' => true,
			'msg' => _text('MSG01015')
		));
} catch(Exception $e) {
	$msg = $e->getMessage();
	echo json_encode(array(
			'success' => false,
			'msg' => $msg
		));
}


?>