<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');


try
{
	$jsonContent_ids = json_decode(str_replace('\\', '', $_POST['content_ids']));
	if ( empty($jsonContent_ids) ) throw new Exception('No content_ids');

	$_t = array();
	foreach ($jsonContent_ids as $entry)
	{
		array_push($_t, $entry->content_id);
	}

	$contents = $db->queryAll("select * from bc_content where content_id in (".join(',', $_t).")");

	die(json_encode(array(
		'success' => true,
		'data' => $contents
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