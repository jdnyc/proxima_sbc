<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$node	= $_REQUEST['node'];
$ud_content_id	= $_REQUEST['ud_content_id'];

if (empty($node) || !is_numeric($node)) {
	$node = '0';
}

$is_mapping_category = false;

if($node == 0) {
	$node = $db->queryOne("
				SELECT	CATEGORY_ID
				FROM	BC_CATEGORY_MAPPING
				WHERE	UD_CONTENT_ID = $ud_content_id
			");
}

$categories = $db->queryAll("
					SELECT	*
					FROM	BC_CATEGORY
					WHERE	PARENT_ID = $node
					ORDER BY SHOW_ORDER
			");


foreach ($categories as $category) {

	$node_category_id = $category['category_id'];

	$data[] = array(
			'id' => $category['category_id'],
			'code' => $category['code'],
			'text' => $category['category_title'],
			//'singleClickExpand' => true,
			'icon' => '/led-icons/folder.gif',
			'read' => $node_grant_array['read'],
			'add' => $node_grant_array['add'],
			'edit' => $node_grant_array['edit'],
			'del' => $node_grant_array['del'],
			'hidden' => $node_grant_array['hidden'],
			'leaf' => (boolean)$category['no_children']
			//,'qtip' => $category['category_id']
		);
}

echo json_encode($data);
?>