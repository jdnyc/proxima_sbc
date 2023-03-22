<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/SGL.class.php');
fn_checkAuthPermission($_SESSION);

try
{
	$content_id = $_POST['content_id'];

	$log_all = $db->queryAll("select sa.logkey, tp.name as type_nm, t.*
		from sgl_archive sa, bc_task t, bc_task_type tp
		where sa.task_id=t.task_id and t.type=tp.type
		and sa.media_id in (select media_id from bc_media where content_id='".$content_id."')
		and sa.logkey is not null
		order by sa.task_id desc");

	$data = array();
	foreach($log_all as $la) {
		if($la['creation_datetime'] != '') {
			$la['creation_datetime'] = date('Y-m-d H:i:s', strtotime($la['creation_datetime']));
		}
		if($la['start_datetime'] != '') {
			$la['start_datetime'] = date('Y-m-d H:i:s', strtotime($la['start_datetime']));
		}
		if($la['complete_datetime'] != '') {
			$la['complete_datetime'] = date('Y-m-d H:i:s', strtotime($la['complete_datetime']));
		}
		array_push($data, $la);
	}


	echo json_encode(array(
		'success' => true,
		'data' => $data,
		'msg' => $log_msg
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