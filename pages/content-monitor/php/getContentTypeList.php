<?php
require_once('DBOracle.class.php');

$db = new Database('nps', 'nps', '192.168.0.102/knnnpsdb01');

$content_type_list = $db->queryAll("SELECT 
										UD_CONTENT_ID AS CONTENT_TYPE_ID, 
										UD_CONTENT_TITLE AS CONTENT_TYPE_NAME 
									FROM BC_UD_CONTENT 
									ORDER BY SHOW_ORDER");

if (!$content_type_list) {
	handleError('db error');
}

echo json_encode(array(
	'success' => true,
	'data' => $content_type_list
));
?>