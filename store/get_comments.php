<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
fn_checkAuthPermission($_SESSION);

$content_id = $_POST['content_id'];
$is_admin = $_SESSION['user']['is_admin'];
$user_id = $_SESSION['user']['user_id'];
try
{
	//content_id, user_id, comments, seq, datetime
	$query = "select * from bc_comments where content_id='".$content_id."' and delete_yn='0' order by seq asc";
	$arr_output = $db->queryAll($query);

	$query_seq = "
		SELECT	SEQ
		FROM	BC_COMMENTS
		WHERE	USER_ID = '$user_id'
		AND		CONTENT_ID = '$content_id'
		AND 	DELETE_YN = '0'
		ORDER BY	SEQ DESC
	";

	$user_last_seq_comment = $db->queryRow($query_seq);
	$data = array();
	foreach($arr_output as $out)
	{
		$con_info = $db->queryRow("select * from bc_content where content_id='".$out['content_id']."'");
		$out['show_info'] = "[".date('Y-m-d H:i:s', strtotime($out['datetime']))."] ".$out['user_nm'].": ";
		$out['datetime_format'] =date('Y-m-d H:i:s', strtotime($out['datetime']));
		$out['version'] = $con_info['version'];
		//$out['is_lasted'] = '<img src="/led-icons/new.png" />';
		if(($out['user_id'] == $user_id && $out['seq'] == $user_last_seq_comment['seq']) || $is_admin == 'Y' ){
			$out['is_lasted'] = 1;
		}else{
			$out['is_lasted'] = 0;
		}

		array_push($data, $out);
	}

	echo json_encode(array(
		'success' => true,
		'data' => $data
	));
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

?>