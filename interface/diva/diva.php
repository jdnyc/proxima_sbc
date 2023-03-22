<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');
require_once('common/common.php');
require_once('common/functions.php');

try
{
	$contents = json_decode($_POST['contents']);
	foreach ($contents as $content)
	{
		$content_id = (int)$content->id;
		$action = (int)$content->action;

		$r = $db->exec("update content set is_deleted=".$action." where content_id=".$content_id);
	}

	die(json_encode(array(
		'success' => true
	)));
}
catch(Exception $e)
{
	die(json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	)));
}