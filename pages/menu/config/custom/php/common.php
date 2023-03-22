<?php
function addExclusiveCategory($ud_content_id, $ud_content_title)
{
	global $db;

	$category_id = getNextSequence();
	$db->exec(sprintf("insert into bc_category (category_id, parent_id, category_title, no_children) values (%d, -1, '%s', '0')", 
						$category_id, $ud_content_title));

	$child_category_id = getNextSequence();
	$db->exec(sprintf("insert into bc_category (category_id, parent_id, category_title, no_children) values (%d, %d, '%s', '1')", 
						$child_category_id, $category_id, $ud_content_title));

	$db->exec("insert into bc_category_mapping (ud_content_id, category_id) values ($ud_content_id, $category_id)");
}
?>