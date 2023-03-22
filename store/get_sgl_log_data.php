<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/SGL.class.php');

try
{
	$content_id = $_POST['content_id'];
	$mode = $_POST['mode'];
	$logkey = $_POST['logkey'];
	$task_id = $_POST['task_id'];

	$sgl = new SGL();

	if($mode == 'archive') {
		$type_query = " and t.type='".ARCHIVE."' ";
	}

	if($logkey != '') {
		$key_query = " and sa.logkey='".$logkey."' ";
	}

	if($task_id != '') {
		$task_query = " and sa.task_id='".$task_id."' ";
	}

	$query = "select sa.*, t.type
		from sgl_archive sa, bc_task t
		where sa.task_id=t.task_id
		and sa.media_id in (select media_id from bc_media where content_id=".$content_id.")
		".$type_query.$key_query.$task_query."
		and sa.logkey is not null
		order by sa.task_id desc";
	$db->setLoadNEWCLOB(true);
	$log_all = $db->queryRow($query);

	$log_msg = $log_all['logtext'];
//  Not only archive, all log get from DB
//	if($log_all['type'] == ARCHIVE) {
//		$log_msg = $log_all['logtext'];
//	} else {
//		$log_msg = array();
//		$log_one = $sgl->FlashNetReadLog($log_all['logkey']);
//		$log_one = (string)$log_one['logs'][0];
//		if($log_one != '') {
//			$log_msg[] = $log_one;
//		}
//	}

	
	$vol_msg = array();
	$vol_all = $db->queryAll("SELECT * FROM SGL_ARCHIVE_VOLUME WHERE CONTENT_ID=".$content_id);
	
	foreach($vol_all as $file) {
		$vol_msg[] = array(
			(string)$file['volume_name']
			,(string)$file['volume_group']
			,(string)$file['status']
			,(string)$file['archive_date']
		);
	}

	if(empty($log_msg)) {
		$log_msg = _text('MSG00148');//결과값이 없습니다.
	}

	if(empty($vol_msg)) {
		$vol_msg = array(_text('MSG00148'));//결과값이 없습니다.
	} else {
		$vol_msg = $vol_msg;
	}

	echo json_encode(array(
		'success' => true,
		'msg' => $log_msg,
		'volume' => $vol_msg,
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