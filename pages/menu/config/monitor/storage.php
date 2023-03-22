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
					,  USED
					, AVAILABLE
					, TOTAL
					, TO_CHAR(TIMESTAMP, 'YYYYMMDDHH24MISS') AS TIMESTAMP
					, CASE DRIVE
						WHEN 'snfsm1 (x:)' THEN '메인'
						WHEN 'snfsb (y:)' THEN '백업'
						ELSE DRIVE END DRIVE_NAME
			FROM	MONITORING_STORAGE A
			WHERE	NOT EXISTS (
						SELECT	ID
						FROM	MONITORING_STORAGE 
						WHERE	DRIVE=A.DRIVE 
						AND		TIMESTAMP>A.TIMESTAMP
					)
			ORDER BY DRIVE
    ) AA
");

echo json_encode(array(
	'success' => true,
	'data' => $result
));