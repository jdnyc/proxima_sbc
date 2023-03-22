<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

try{

	$parent_content_ids = $_POST['parent_content_id'];
	$p_content_ids = json_decode($parent_content_ids, true);
	$content_id_array = array();
	
	foreach ($p_content_ids as $parent_content_id) {
		
		$list_data = $db->queryAll("SELECT CONTENT_ID FROM BC_CONTENT WHERE PARENT_CONTENT_ID = $parent_content_id");
		
		array_push($content_id_array, $parent_content_id);

		foreach ($list_data as $data) {
			array_push($content_id_array, $data['content_id']);
		}
	}
	
	echo json_encode(array(
		'success' => true,
		'data' => $content_id_array,
	));

}catch(Exception $e) {
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}
?>