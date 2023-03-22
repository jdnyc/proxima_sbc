<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$id = $_POST['node'];

$nodes = $db->queryAll('select * from bc_category where parent_id=' . $id);

$categories = array();
foreach ($nodes as $node) {
	array_push($categories, array(
		'id' => $node['category_id'],
		'text' => $node['category_title'],
		'leaf' => $node['no_children']
	));
}

echo json_encode($categories);