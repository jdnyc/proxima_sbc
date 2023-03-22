<?php
require_once('DBOracle.class.php');

$db = new Database('nps', 'nps', '192.168.0.102/knnnpsdb01');

$content_type_id = $_POST['content_type_id'];

$content_type_list = $db->queryAll("SELECT 
										USR_META_FIELD_ID AS ID, USR_META_FIELD_TITLE AS NAME,  
										USR_META_FIELD_TYPE AS TYPE, 
										IS_REQUIRED AS REQUIRED, DEFAULT_VALUE
									FROM BC_USR_META_FIELD 
									WHERE UD_CONTENT_ID = $content_type_id
									ORDER BY SHOW_ORDER");

if (!$content_type_list) {
	handleError('db error');
}

echo json_encode(array(
	'success' => true,
	'data' => $content_type_list
));
?>