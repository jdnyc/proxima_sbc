<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$list = $db->queryAll("select m.name, m.meta_table_id from content_type c, meta_table m where c.name='{$_POST['type']}' and c.content_type_id=m.content_type_id");

echo json_encode(array(
	'success' => true,
	'data' => $list
));

?>