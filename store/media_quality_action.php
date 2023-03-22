<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
session_start();
/*
edit //QC 이상없음 체크버튼
edit_check // QC 이상없음 체크버튼 누를 시 비교
get_request //NPS 정보 가져오기
*/
try
{
	$user_id = $_SESSION['user']['user_id'];
	$content_id = $_POST['content_id'];
	$action = $_POST['action'];

	if($action == 'edit') {
//Mark as not error
		$r = $db->exec("update bc_media_quality set
			no_error = '1'
        where media_id in (select media_id from bc_media where content_id='".$content_id."')");
        
        $r = $db->exec("update bc_content_status set  QC_CNFIRM_AT='1',QC_CNFRMR='$user_id' where content_id='".$content_id."' ");
		insertLog('qc_check', $user_id, $content_id, _text('MN02368'));
	}

	$msg = '성공';
	echo json_encode(array(
		'success' => true,
		'msg' => $msg,
		'query' => $query
	));
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage(),
		'last_query' => $db->last_query
	));
}

?>