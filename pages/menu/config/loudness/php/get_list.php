<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

try{
	$ud_contents = $db->queryAll("
					SELECT	UD.*, COALESCE(L.USE_YN, 'N') AS USE_YN, L.MODIFIED_USER_ID, L.MODIFIED_DATETIME,
							(SELECT USER_NM FROM BC_MEMBER WHERE USER_ID = L.MODIFIED_USER_ID) AS MODIFIED_USER_NM
					FROM	BC_UD_CONTENT UD
							LEFT OUTER JOIN TB_LOUDNESS_CONFIG L ON L.UD_CONTENT_ID = UD.UD_CONTENT_ID
					ORDER BY UD.SHOW_ORDER ASC
				");
	
	echo json_encode(array(
			'success' => true,
			'data'	=> $ud_contents,
			'query' => $db->last_query
	));
	
} catch (Exception $e) {
	echo json_encode(array(
			'success' => false,
			'msg' => $e->getMessage(),
			'last_query' => $db->last_query
	));
}
?>