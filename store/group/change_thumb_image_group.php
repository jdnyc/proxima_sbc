<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
try{
	$content_id = $_POST['content_id'];
	$thumb_content_id = $_POST['thumb_content_id'];

	$update_query = "UPDATE BC_CONTENT SET THUMBNAIL_CONTENT_ID = $thumb_content_id WHERE CONTENT_ID = $content_id";
	$db->exec($update_query);

	echo json_encode(array(
			'success' => true,
			'query'	=>	 $update_query
	));

} catch(Exception $e) {

	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage(),
		'query' => $update_query
	));
}
?>