<?php
require_once '../lib/config.php';

$data = $db->queryAll("
			SELECT	DESCRIPTION, LOGIN_ID, LOGIN_PW, NAME, PATH,
					PATH_FOR_MAC, STORAGE_ID, TYPE, VIRTUAL_PATH,
					NAME || '[' || PATH ||']' AS DISPLAY_NAME
			FROM	BC_STORAGE
			ORDER	BY NAME	
		");

echo json_encode(array(
    'success' => true,
    'data' => $data
));
?>