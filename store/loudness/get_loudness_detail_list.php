<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

try
{
	$loudness_id = $_POST['loudness_id'];
	
	if(empty($loudness_id)) {
		throw new Exception(_text('MSG00021'));
	}
	
//	$db->setLoadNEWCLOB(true);
// 	$lists = $db->queryAll("
// 				SELECT	L.*
// 				FROM	TB_LOUDNESS_LOG L
// 				WHERE	L.LOUDNESS_ID = $loudness_id
// 				ORDER BY L.LOUDNESS_LOG_ID DESC
// 			");
	$lists = $db->queryAll("
				SELECT	L.*
				FROM	TB_LOUDNESS_MEASUREMENT_LOG L
				WHERE	LOUDNESS_ID = $loudness_id
				ORDER BY LOUDNESS_MEASUREMENT_LOG_ID ASC
			");

// 	if(empty($lists)) {
// 		$lists = _text('MSG00148');//결과값이 없습니다.
// 	}

	echo json_encode(array(
		'success' => true,
		'data'	=> $lists,
		'query' => $query
	));
}
catch ( Exception $e )
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage(),
		'last_query' => $db->last_query
	));
}

?>