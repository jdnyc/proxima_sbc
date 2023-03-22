<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');


$list = $db->queryAll("select * from bc_ud_content order by show_order");


echo json_encode(array(
	'success' => true,
	'data' => $list
));
?>