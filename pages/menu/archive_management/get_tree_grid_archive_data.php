<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

global $db;

try{

	$category_id = $_POST['node'];
	
	if(empty($category_id) || strstr($category_id, 'xnode') !== false) {
		$category_id = 0;
	}
	
	$result = array();
	
	$category_infos = $db->queryAll("
						SELECT	*
						FROM	BC_CATEGORY
						WHERE	PARENT_ID = '$category_id'
						ORDER BY SHOW_ORDER ASC
					");
	
	foreach($category_infos as $info) {
		$category = $info['category_id'];
		$category_title = $info['category_title'];
		$has_child = (boolean)$info['no_children'];
	
		$row = $db->queryRow("
					SELECT	C.*, M.USER_NM AS EDIT_USER_NM
					FROM	BC_CATEGORY_ENV_ARCHIVE C
							LEFT OUTER JOIN BC_MEMBER M ON C.EDIT_USER_ID = M.USER_ID
					WHERE	C.CATEGORY_ID = '$category'
				");
	
		$data['id']	= $category;
		$data['category_title'] = $category_title;
		$data['leaf'] = $has_child;
		$data['icon'] = '/led-icons/folder.gif';
		
		$data['archive_auth_start_time'] = $row['archive_s_time'] ? $row['archive_s_time'] : '-';
		$data['archive_auth_end_time']   = $row['archive_e_time'] ? $row['archive_e_time'] : '-';
	
		$data['archive_method'] = $row['archive_method'] ? : 'M';
		$data['archive_method_nm'] = methodMapping($row['archive_method']);
	
		$data['edit_datetime'] = $row['edit_datetime'];
		$data['edit_user_id'] = $row['edit_user_id'];
		$data['edit_user_nm'] = $row['edit_user_nm'];
	
		array_push($result, $data);
	}
	
	echo json_encode($result);
} catch(Exception $e) {
	echo json_encode();
}


function methodMapping($val) {
	switch($val) {
		case 'A' :
			$method = '자동';
		break;
		case 'M' :
			$method = '수동';
		break;
		default :
			$method = '수동';
		break;
	}

	return $method;
}

?>
