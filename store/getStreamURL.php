<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');


try
{
	$id = $_POST['content_id'];

	$stream_url = $db->queryOne("select path from bc_media where content_id=".$id." and media_type='proxy'");

	die(json_encode(array(
		'success' => true,
		'stream_url' => $stream_url
	)));
}
catch (Exception $e)
{
	die(json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	)));
}

?>