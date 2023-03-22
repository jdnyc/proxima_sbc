<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

try
{
	$content_id = $_POST['content_id'];
	
	if(empty($content_id)) {
		throw new Exception(_text('MSG00016'));
	}
	
	$lists = $db->queryAll("
				SELECT	L.*, (SELECT USER_NM FROM BC_MEMBER WHERE USER_ID = L.REQ_USER_ID) AS REQ_USER_NM
				FROM	TB_LOUDNESS L
				WHERE	L.CONTENT_ID = $content_id
				ORDER BY L.LOUDNESS_ID DESC
			");

// 	if(empty($lists)) {
// 		$lists = _text('MSG00148');//결과값이 없습니다.
// 	}

	echo json_encode(array(
		'success' => true,
		'data'	=> $lists,
		'query' => $query
	));
	
} catch ( Exception $e ) {
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage(),
		'last_query' => $db->last_query
	));
}

?>