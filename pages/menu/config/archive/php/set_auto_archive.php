<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/util.php';
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

switch($_REQUEST['action']) {
	case 'get':
		$setValue = $db->queryOne("
				SELECT CODE
				FROM BC_CODE
				WHERE NAME = 'auto_archive_config'
			    ");
		echo json_encode(array(
			'success' => true,
			'setValue' => $setValue
		));
	break;
	case 'save':
		$save_value = $_REQUEST['set_auto_archive'];
		$bc_code_type = $db->queryOne("SELECT ID FROM BC_CODE_TYPE WHERE CODE = 'AUTO_ARCHIVE_CONFIG'");
		$db->exec("UPDATE BC_CODE SET CODE = '$save_value' WHERE CODE_TYPE_ID = '$bc_code_type'");
		
		echo json_encode(array(
				'success' => true,
				'msg' => '자동 아카이브 실행 설정값이 변경되었습니다'
		));
		
	break;
	default: 
		echo json_encode(array(
			'success' => false,
			'msg' => '액션이 정의 되어있지 않습니다.'
		));
	break;
}
?>
