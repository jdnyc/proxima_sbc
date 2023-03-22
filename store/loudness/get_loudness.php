<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$data = array();
$content_id = $_POST['content_id'];
$content_id = 223;
try {
	
	$loudness_lists = $db->queryAll("
							SELECT	L.*, (SELECT USER_NM FROM BC_MEMBER WHERE USER_ID = L.REQ_USER_ID) AS REQ_USER_NM
							FROM	TB_LOUDNESS L
							WHERE	L.CONTENT_ID = $content_id
							ORDER BY L.LOUDNESS_ID DESC
					");
	
	foreach($loudness_lists as $list) {
		$loudness_id = $list['loudness_id'];
		
		$db->setLoadNEWCLOB(true);
		$logs = $db->queryAll("
					SELECT	L.*
					FROM	TB_LOUDNESS_LOG L
					WHERE	L.LOUDNESS_ID = $loudness_id
					ORDER BY L.LOUDNESS_LOG_ID DESC
				");
		
		$loudness_logs = array();
		
		foreach($logs as $log) {
			array_push($loudness_logs, $log);
		}
		
		$list['logs'] = $loudness_logs;
		
		array_push($data, $list);
		
	}
	
	
	echo json_encode(array(
		'success' => true,
		'total' => count($rows),
		'data' => $data
	));
	
} catch(Exception $e) {
	switch($e->getCode()){
		case ERROR_QUERY:
			die(json_encode(array(
				'success' => false,
				'msg' => $e->getMessage().'('.$db->last_query.')'
			)));
		break;
	}
}
?>