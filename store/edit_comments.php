<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$mode = $_POST['mode'];
$user_id = $db->escape($_SESSION['user']['user_id']);
$user_nm = $db->escape($_SESSION['user']['KOR_NM']);
$content_id = $_POST['content_id'];
$comment_user_id = $_POST['comment_user_id'];

$seq = $_POST['seq'];
$today = date('YmdHis');

try
{
	$text = $db->escape($_POST['text']);
	$text = substr($text,0,4000);
	switch($mode)
	{
		case 'insert':
			$new_seq = $db->queryOne("select max(seq) from bc_comments where content_id='".$content_id."'");
			if(empty($new_seq))
			{
				$new_seq = 1;
			}
			else
			{
				$new_seq = (int)$new_seq + 1;
			}
			$query = "insert into bc_comments(content_id, user_id, user_nm, comments, seq, datetime, delete_yn)
						values(".$content_id.",'".$user_id."','".$user_nm."','".$text."','".$new_seq."','".$today."', '0')";
			$db->exec($query);
			markLastModiDate($content_id);
		break;
		case 'update':

		break;
		case 'delete':
			$query = "	UPDATE 	BC_COMMENTS
						SET		DELETE_YN	= '1'
						WHERE 	content_id 	='".$content_id."'
						AND 	user_id 	= '".$comment_user_id."'
						AND 	seq 		= '".$seq."'
						";
			$db->exec($query);
		break;
	}

	echo json_encode(array(
		'success' => true,
		'query' => $query,
		'data' => $data,
		'msg' => 'success'
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