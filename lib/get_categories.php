<?php
require_once("../config.php");

$node = $_REQUEST['node'];

if(empty($node) || !is_numeric($node)){
	$node = '0';
}

$categories = $mdb->queryAll("select * from bc_category where parent_id = ".$node." order by show_order");

foreach ($categories as $category) {
	$data[] = array(
		'id' => $category['category_id'],
		'code' => $category['code'],
		'text' => $category['category_title'],
		'singleClickExpand' => true,
		'leaf' => (boolean)$category['no_children']
	);
}

echo json_encode($data);
?>