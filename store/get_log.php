<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$content_id = $_POST['content_id'];

$result = $db->queryAll("
			SELECT *
			  FROM BC_LOG
			 WHERE CONTENT_ID = $content_id
			   AND ACTION = 'edit'
");

echo json_encode(array(
	'success' => true,
	'data' => $result
));
