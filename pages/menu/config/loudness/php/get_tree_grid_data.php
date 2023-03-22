<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

try {

	$category_id	= $_REQUEST['node'];
	$ud_content_id	= $_REQUEST['ud_content_id'];
	
	if(empty($category_id) || strstr($category_id, 'xnode') !== false)
	{
		$category_id = 0;
	}
	
	if($category_id == 0 && !empty($ud_content_id)) {
		$category_id = $db->queryOne("
							SELECT CATEGORY_ID
							FROM BC_CATEGORY_MAPPING
							WHERE UD_CONTENT_ID = '$ud_content_id'
						");
	}
	$result = array();
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] $category_id ===> '.$category_id."\r\n", FILE_APPEND);
	if( empty($category_id) ){
		$category_id = 0;
	}
	
	$query = "
			SELECT	CATEGORY_ID, CATEGORY_TITLE
			FROM	BC_CATEGORY
			WHERE	PARENT_ID = '$category_id'
			ORDER BY SHOW_ORDER ASC
	";
	$category_infos = $db->queryAll($query);
	
	foreach($category_infos as $info) {
		$category = $info['category_id'];
		$category_title = $info['category_title'];
		$has_child = has_child($category);
		if($has_child == true) {
			$child = 'closed';
		} else {
			$child = 'open';
		}
	
		$query = "
				SELECT	*
				FROM	TB_LOUDNESS_CONFIGURATION
				WHERE	CATEGORY_ID = $category
				AND		UD_CONTENT_ID = $ud_content_id
		";
		
		$row = $db->queryRow($query);
	
		$data['id']					= $category;
		$data['category_title']		= $category_title;
		$data['ud_content_id']		= $row['ud_content_id'];
		$data['is_loudness']		= $row['is_loudness'];
		$data['is_correct']			= $row['is_correct'];
		$data['reg_user_id']		= $row['reg_user_id'];
		$data['reg_datetime']		= $row['reg_datetime'];
	
		array_push($result, $data);
	}
	
	echo json_encode($result);
	
} catch (Exception $e) {
	echo json_encode($e->getMessage());
}

function has_child($id) {
	global $db;
	$query = "
		SELECT	COUNT(*)
		FROM	BC_CATEGORY
		WHERE	PARENT_ID = '$id'
	";
	$has_child = $db->queryOne($query);

	if($has_child > 0) {
		return true;
	} else {
		return false;
	}
}

?>