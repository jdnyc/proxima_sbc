<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/db.php';

$user_id = $_SESSION['user']['user_id'];

try {
	if (empty($user_id) || $user_id=='temp') {
        throw new Exception("로그인해주세요.");
    }

	$category_id	= $_POST['id'];
	$parent_category_id	= $_POST['parent_id'];

	$modified_time	= date('YmdHis');

	$topic_full_path = getCategoryFullPath($category_id);
    $topic_full_path_title = getCategoryPathTitle($topic_full_path);

    $data = array(
        'IS_DELETED' => 1
    );
    $db->update('BC_CATEGORY', $data, "category_id = $category_id");
    $db->update('BC_CATEGORY_TOPIC', $data, "category_id = $category_id");

	$description = $category_id.' 삭제('.$topic_full_path_title.')';
	$action = 'topic del';

	insertLogTopic($action, $user_id, $category_id, $description);

	$msg = _text('MSG02122');//Deleted.

	echo json_encode(array(
		'success' => true,
		'msg' => $msg
	));
}
catch ( Exception $e )
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

?>
