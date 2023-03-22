<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$result = $db->queryAll("
				SELECT *
				  FROM BC_MODULE_INFO
				 where ACTIVE = '1'
			  ORDER BY NAME
");

echo json_encode(array(
	'success' => true,
	'data' => $result
));
