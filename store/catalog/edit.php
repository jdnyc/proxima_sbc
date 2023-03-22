<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/db.php');


try
{
	$user_id = $_SESSION['user']['user_id'];
	$scene_id = $_POST['scene_id'];
	$comments = $db->escape($_POST['comments']);
	$action = $_POST['action'];
	$story_board_id = $_POST['story_board_id'];
	$title = $_POST['title'];
	$content = $_POST['content'];
	$peoples = $_POST['peoples'];
	$cur_time = date('YmdHis');

	switch($action)
	{
		case 'edit_comment':
			queryExec("	UPDATE 	BC_SCENE
						SET 	COMMENTS='$comments' 
						WHERE 	SCENE_ID=".$scene_id
						);
			
			echo json_encode(array(
				'success' => true
			));
		break;
		case 'edit_story_board_title':
			$content_id = $db->queryOne("
				SELECT	CONTENT_ID
				FROM	BC_MEDIA
				WHERE	MEDIA_ID IN (
						SELECT	MEDIA_ID
						FROM	BC_STORY_BOARD
						WHERE	STORY_BOARD_ID=".$story_board_id."
						)
			");
			queryExec("	UPDATE 	BC_STORY_BOARD 
						SET 	TITLE='$title',
								CONTENT='$content',
								PEOPLES='$peoples'
						WHERE 	STORY_BOARD_ID=".$story_board_id);
			searchUpdate($content_id);
			echo json_encode(array(
				'success' => true
			));
		break;
		case 'delete_sub_story_board':
			$content_id = $db->queryOne("
				SELECT	CONTENT_ID
				FROM	BC_MEDIA
				WHERE	MEDIA_ID IN (
						SELECT	MEDIA_ID
						FROM	BC_STORY_BOARD
						WHERE	STORY_BOARD_ID=".$story_board_id."
						)
			");
			queryExec("	UPDATE 	BC_STORY_BOARD 
						SET 	IS_DELETED = 'Y',
								DELETED_USER_ID = '$user_id',
								DELETED_DATE = '$cur_time'
						WHERE 	STORY_BOARD_ID=".$story_board_id);
			searchUpdate($content_id);
			echo json_encode(array(
				'success' => true
			));
		break;
	}
}
catch ( Exception $e )
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

?>