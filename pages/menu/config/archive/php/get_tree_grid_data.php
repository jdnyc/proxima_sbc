<?php
//작성일 : 2013.03.08
//작성자 : 임찬모
//아카이브 관리 트리그리드를 불러오는 페이지

//2013.03.25 수정 del 정보 수정
//2013.04.25 수정 abrogate 정보 수정

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
fn_checkAuthPermission($_SESSION);
global $db;

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

if( empty($category_id) ){
	$category_id = 0;
}

$query = "select category_id, category_title from bc_category where parent_id = '$category_id' order by show_order asc";
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

	$query = "select * from bc_category_env where category_id = '$category' AND UD_CONTENT_ID = '$ud_content_id'";
	$row = $db->queryRow($query);

	$data['id']						= $category;
	$data['category_title']			= $category_title;
	$data['ud_content_id']			= $row['ud_content_id'];
	$data['is_archive']				= $row['is_archive'];
	$data['archive_group']			= $row['archive_group'];
	$data['archive_priority']		= $row['archive_priority'];
	$data['archive_time']			= $row['archive_time'];
	$data['archive_delete_time']	= $row['archive_delete_time'];
	$data['storage_delete_time']	= $row['storage_delete_time'];
	$data['restore_priority']		= $row['restore_priority'];
	$data['restore_delete_time']	= $row['restore_delete_time'];

	array_push($result, $data);
}

echo json_encode($result);

function has_child($id) {
	global $db;
	$query = "select count(*) from bc_category where parent_id = '$id'";
	$has_child = $db->queryOne($query);

	if($has_child > 0) {
		return true;
	} else {
		return false;
	}
}

?>