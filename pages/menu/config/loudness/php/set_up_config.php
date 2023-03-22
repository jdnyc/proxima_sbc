<?php
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
//require_once($_SERVER['DOCUMENT_ROOT']."/lib/DBOracle.class.php");
@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] post ===> '.print_r($_POST, true)."\r\n", FILE_APPEND);
//common parameter

$ud_content_id	= $_REQUEST['ud_content_id'];
$category_id	= $_REQUEST['category'];
$is_loudness	= $_REQUEST['is_loudness'];
$is_correct		= $_REQUEST['is_correct'];
$apply_child	= $_REQUEST['apply_child'];
$user_id		= $_REQUEST['user_id'];
$now 			= date('YmdHis');

try {
	
	if(empty($is_loudness)) {
		$is_loudness = 'N';
		$is_correct = 'N';
	}
	// apply to child categories also
	if(!empty($apply_child) && $apply_child == 'Y') {
		// get category list include child category
		if( DB_TYPE == 'oracle' ){
			$query = "
				SELECT	CATEGORY_ID
				FROM	BC_CATEGORY
				START WITH CATEGORY_ID = $category_id
				CONNECT BY PRIOR CATEGORY_ID = PARENT_ID
			";
		} else {
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
	
	$categories = $db->queryAll($query);	

		foreach($categories as $category) {
			$c_id = $category['category_id'];
			$is_category_set = $db->queryOne("
									SELECT	COUNT(*)
									FROM	TB_LOUDNESS_CONFIGURATION
									WHERE	CATEGORY_ID = $c_id
									AND	UD_CONTENT_ID = '$ud_content_id'
								");
			if($is_loudness == 'Y') {
				// loudness_use
				if($is_category_set == 0) {
					//add
					$r = $db->exec("
							INSERT INTO TB_LOUDNESS_CONFIGURATION
								(CATEGORY_ID, UD_CONTENT_ID, IS_LOUDNESS, IS_CORRECT, REG_USER_ID, REG_DATETIME)
							VALUES
								($c_id, $ud_content_id, '$is_loudness', '$is_correct', '$user_id', '$now')
						");
				} else {
					//update
					$r = $db->exec("
							UPDATE	TB_LOUDNESS_CONFIGURATION
							SET		IS_LOUDNESS = '$is_loudness',
									IS_CORRECT = '$is_correct',
									REG_USER_ID = '$user_id',
									REG_DATETIME = '$now'
							WHERE	CATEGORY_ID = '$c_id'
							AND		UD_CONTENT_ID = '$ud_content_id'
						");
				}
			} else {
				// archive_not_use
				if($is_category_set == 0) {
					// add
					$r = $db->exec("
							INSERT INTO TB_LOUDNESS_CONFIGURATION
								(CATEGORY_ID, UD_CONTENT_ID, IS_LOUDNESS, IS_CORRECT, REG_USER_ID, REG_DATETIME)
							VALUES
								($c_id, $ud_content_id, '$is_loudness', '$is_correct', '$user_id', '$now')
						");
				} else {
					// update
					$r = $db->exec("
							UPDATE	TB_LOUDNESS_CONFIGURATION
							SET		IS_LOUDNESS = '$is_loudness',
									IS_CORRECT = '$is_correct',
									REG_USER_ID = '$user_id',
									REG_DATETIME = '$now'
							WHERE	CATEGORY_ID = '$c_id'
							AND		UD_CONTENT_ID = '$ud_content_id'
						");
				}
			}
		}
		
	} else {
		// apply only current category
		$is_category_set = $db->queryOne("
							SELECT	COUNT(*)
							FROM	TB_LOUDNESS_CONFIGURATION
							WHERE	CATEGORY_ID = $category_id
							AND	UD_CONTENT_ID = '$ud_content_id'
						");
		
		if($is_category_set == 0) {
			// add
			$r = $db->exec("
						INSERT INTO TB_LOUDNESS_CONFIGURATION
							(CATEGORY_ID, UD_CONTENT_ID, IS_LOUDNESS, IS_CORRECT, REG_USER_ID, REG_DATETIME)
						VALUES
							($category_id, $ud_content_id, '$is_loudness', '$is_correct', '$user_id', '$now') 
				");
		} else {
			// update
			$r = $db->exec("
						UPDATE	TB_LOUDNESS_CONFIGURATION
						SET		IS_LOUDNESS = '$is_loudness',
								IS_CORRECT = '$is_correct',
								REG_USER_ID = '$reg_user_id',
								REG_DATETIME = '$now'
						WHERE	CATEGORY_ID = $category_id
						AND		UD_CONTENT_ID = $ud_content_id
				");
		}
	}
	
	echo json_encode(array(
			'success' => true,
			'msg' => _text('MSG00087')
	));
	
} catch (Exception $e) {
	$msg = $e->getMessage();

	echo json_encode(array(
		'success' => false,
		'msg' => $msg
	));
}
?>

