<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
fn_checkAuthPermission($_SESSION);
$result = $db->queryAll("
	SELECT	* 
	FROM	(
			SELECT	ID
					, NAME
					, IP
					, USED_CPU
					, USED_MEMORY
					, TO_CHAR(TIMESTAMP, 'YYYYMMDDHH24MISS') AS TIMESTAMP
			FROM	MONITORING_SERVER A
			WHERE	NOT EXISTS (
						SELECT	'X'
						FROM	MONITORING_SERVER 
						WHERE	NAME=A.NAME 
						AND		TIMESTAMP>A.TIMESTAMP
					)
			ORDER BY NAME
	) AA
");

echo json_encode(array(
	'success' => true,
	'data' => $result
));
