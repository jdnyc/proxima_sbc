<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');


$all =  array(
	'ud_content_id'		=> -1,
	'ud_content_title'	=> '전체'
);

$list = $db->queryAll("select * from bc_ud_content order by show_order");

array_unshift($list, $all);

echo json_encode(array(
	'success' => true,
	'data' => $list
));
?>