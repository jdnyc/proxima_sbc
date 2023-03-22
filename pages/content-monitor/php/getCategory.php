<?php
session_start();

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$node = $_POST['node'];
$ud_content_id = $_POST['content_type_id'];

if ($node == 'root') {
	$category_id = $db->queryOne("select category_id from bc_category_mapping where ud_content_id=".$ud_content_id);
}
else {
	$category_id = $node;
}

$nodes = $db->queryAll("SELECT  CATEGORY_ID AS ID, 
								CATEGORY_TITLE AS TEXT
						FROM BC_CATEGORY 
						WHERE PARENT_ID=".$category_id);
echo json_encode($nodes);
?>