<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$program_id = $_POST['id'];

try
{
	$content = $db->queryRow("
		SELECT	MAX(CONTENT_ID) AS CONTENT_ID,
				COUNT(*) AS TOTAL
		FROM 	BC_CONTENT
		WHERE 	CATEGORY_ID = ".$program_id
	);

	echo json_encode(array(
		'success'	=> true,
		'content_id' => $content['content_id'],
		'total' => $content['total']
	));
}
catch (Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

?>
