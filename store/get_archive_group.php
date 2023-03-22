<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

try {
	$groups = $db->queryAll("
				SELECT C.CODE, C.NAME
				FROM BC_CODE C, BC_CODE_TYPE CT
				WHERE C.CODE_TYPE_ID = CT.ID
				AND CT.CODE = 'sgl_group_list'
				ORDER BY C.ID ASC
			");

	echo json_encode(array(
		'success' => true,
		'data' => $groups
	));
} catch(Exception $e) {
	echo json_encode(array(
		'success'	=> false,
		'msg'		=> $e->getMessage()
	));
}
?>