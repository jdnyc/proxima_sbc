<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
//require_once($_SERVER['DOCUMENT_ROOT']."/lib/DBOracle.class.php");

//common parameter
$action			= $_REQUEST['action'];
$ud_content_id	= $_REQUEST['ud_content_id'];
$category_id	= $_REQUEST['category'];

//archive parameter
$is_archive				= $_REQUEST['is_archive'];
$archive_group			= $_REQUEST['archive_group'];
$archive_priority		= $_REQUEST['archive_priority'];
$archive_time			= $_REQUEST['archive_time'];
$archive_delete_time	= $_REQUEST['archive_delete_time'];
$storage_delete_time	= $_REQUEST['storage_delete_time'];
//restore parameter
$restore_delete_time	= $_REQUEST['restore_delete_time'];
$restore_priority		= $_REQUEST['restore_priority'];

try {
	// get category list include child category
	if( DB_TYPE == 'oracle' ){
		$query = "
			SELECT CATEGORY_ID
			FROM BC_CATEGORY
			START WITH CATEGORY_ID = $category_id
			CONNECT BY PRIOR CATEGORY_ID = PARENT_ID
		";
	}else{
		$query = "
			WITH RECURSIVE q AS (
				SELECT	ARRAY[po.CATEGORY_ID] AS HIERARCHY
						,po.CATEGORY_ID
						,po.CATEGORY_TITLE
						,po.PARENT_ID
						,1 AS LEVEL
				FROM	BC_CATEGORY po
				WHERE	po.CATEGORY_ID = $category_id
				AND		po.IS_DELETED = 0
				UNION ALL
				SELECT	q.HIERARCHY || po.CATEGORY_ID
						,po.CATEGORY_ID
						,po.CATEGORY_TITLE
						,po.PARENT_ID
						,q.level + 1 AS LEVEL
				FROM	BC_CATEGORY po
						JOIN q ON q.CATEGORY_ID = po.PARENT_ID
				WHERE	po.IS_DELETED = 0
			)
			SELECT	CATEGORY_ID
					,CATEGORY_TITLE
					,PARENT_ID
			FROM	q
			WHERE 	CATEGORY_ID != 0
			AND		PARENT_ID = 0
			ORDER BY HIERARCHY
		";
	}
	//$query = "
			//SELECT CATEGORY_ID
			//FROM BC_CATEGORY
			//START WITH CATEGORY_ID = $category_id
			//CONNECT BY PRIOR CATEGORY_ID = PARENT_ID
	//";
	$categories = $db->queryAll($query);

	if($action == 'archive') {

		foreach($categories as $category) {
			$c_id = $category['category_id'];
			$is_category_set = $db->queryOne("
									SELECT COUNT(*)
									FROM BC_CATEGORY_ENV
									WHERE CATEGORY_ID = '$c_id'
									AND UD_CONTENT_ID = '$ud_content_id'
								");
			if($is_archive == 'Y') {
				// archive_use
				if($is_category_set == 0) {
					//add
					$r = $db->exec("
							INSERT INTO BC_CATEGORY_ENV
								(CATEGORY_ID, UD_CONTENT_ID, IS_ARCHIVE, ARCHIVE_GROUP, ARCHIVE_PRIORITY, ARCHIVE_TIME, STORAGE_DELETE_TIME, ARCHIVE_DELETE_TIME)
							VALUES
								('$c_id', '$ud_content_id', '$is_archive', '$archive_group', '$archive_priority', '$archive_time', '$storage_delete_time', '$archive_delete_time')
						");
				} else {
					//update
					$r = $db->exec("
							UPDATE BC_CATEGORY_ENV
							SET IS_ARCHIVE = '$is_archive', ARCHIVE_GROUP = '$archive_group', ARCHIVE_PRIORITY = '$archive_priority',
								ARCHIVE_TIME = '$archive_time', STORAGE_DELETE_TIME = '$storage_delete_time', ARCHIVE_DELETE_TIME = '$archive_delete_time'
							WHERE CATEGORY_ID = '$c_id'
							AND UD_CONTENT_ID = '$ud_content_id'
						");
				}
			} else {
				// archive_not_use
				if($is_category_set == 0) {
					// add
					$r = $db->exec("
							INSERT INTO BC_CATEGORY_ENV
								(CATEGORY_ID, UD_CONTENT_ID, IS_ARCHIVE)
							VALUES
								('$c_id', '$ud_content_id', '$is_archive')
						");
				} else {
					// update
					$r = $db->exec("
							UPDATE BC_CATEGORY_ENV
							SET IS_ARCHIVE = '$is_archive', ARCHIVE_GROUP = '$archive_group', ARCHIVE_PRIORITY = '$archive_priority',
								ARCHIVE_TIME = '$archive_time', STORAGE_DELETE_TIME = '$storage_delete_time', ARCHIVE_DELETE_TIME = '$archive_delete_time'
							WHERE CATEGORY_ID = '$c_id'
							AND UD_CONTENT_ID = '$ud_content_id'
						");
				}
			}
		}
		echo json_encode(array(
			'success' => true,
			'msg' => _text('MSG00087')
		));
	} else if ($action == 'restore') {
		foreach($categories as $category) {
			$c_id = $category['category_id'];
			$is_category_set = $db->queryOne("
									SELECT COUNT(*)
									FROM BC_CATEGORY_ENV
									WHERE CATEGORY_ID = $c_id
								");
			if($is_category_set == 0){
				// add
				$r = $db->exec("
						INSERT INTO BC_CATEGORY_ENV
							(CATEGORY_ID, UD_CONTENT_ID, RESTORE_PRIORITY, RESTORE_DELETE_TIME)
						VALUES
							('$c_id', '$ud_content_id', '$restore_priority', '$restore_delete_time')
					");
			} else {
				// update
				$r = $db->exec("
						UPDATE BC_CATEGORY_ENV
						SET RESTORE_PRIORITY = '$restore_priority', RESTORE_DELETE_TIME = '$restore_delete_time'
						WHERE CATEGORY_ID = '$c_id'
						AND UD_CONTENT_ID = '$ud_content_id'
					");
			}
		}
		echo json_encode(array(
			'success' => true,
			'msg' => _text('MSG00087')
		));
	} else {
		$msg = _text('MSG00157');
		throw new Exception(_text('MSG00157'));
	}
} catch (Exception $e) {
	$msg = $e->getMessage();

	echo json_encode(array(
		'success' => false,
		'msg' => $msg
	));
}
?>

