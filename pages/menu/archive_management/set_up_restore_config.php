<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

global $db;
$type = $_REQUEST['type'];
$category_id = $_REQUEST['category_id'];
$post_method = $_REQUEST['restore_method'];
$user_id = $_REQUEST['user_id'];
$s_period = $_REQUEST['s_period'];
$e_period = $_REQUEST['e_period'];

try{
	
	$cur_time = date('YmdHis');	

	/*
	$categories = $db->queryAll("
					SELECT	CATEGORY_ID
					FROM	BC_CATEGORY
					START WITH CATEGORY_ID = $category_id
					CONNECT BY PRIOR CATEGORY_ID = PARENT_ID
			");
	*/
	$categories = $db->queryAll("
		WITH RECURSIVE RC AS (
			SELECT	CONCAT('/',r.CATEGORY_TITLE) AS hierarchy
					,R.CATEGORY_ID
					,R.CATEGORY_TITLE
					,R.PARENT_ID
					,1 AS LEVEL
			FROM    BC_CATEGORY R
			WHERE    R.CATEGORY_ID = $category_id
			UNION ALL
			SELECT	CONCAT_WS('/', RC.hierarchy, R.CATEGORY_TITLE)  As TEXT
					,R.CATEGORY_ID
					,R.CATEGORY_TITLE
					,R.PARENT_ID
					,RC.LEVEL + 1 AS LEVEL
			FROM    BC_CATEGORY R
					JOIN RC ON RC.CATEGORY_ID = R.PARENT_ID
		)
		SELECT * FROM RC
		ORDER BY RC.hierarchy
	");

	foreach($categories as $category) {
		$c_id = $category['category_id'];
		
		$is_category = $db->queryOne("
						SELECT	COUNT(*)
						FROM	BC_CATEGORY_ENV_RESTORE
						WHERE	CATEGORY_ID = $c_id
					");
		if($is_category == 0) {
			//없으면 신규 추가
			$db->exec( "
				INSERT INTO BC_CATEGORY_ENV_RESTORE
					(CATEGORY_ID, RESTORE_METHOD, RESTORE_S_TIME, RESTORE_E_TIME, EDIT_USER_ID, EDIT_DATETIME)
				values
					($c_id, '$post_method', '$s_period', '$e_period', '$user_id','$cur_time')
			");
		} else {
			//있으면 업데이트
			$db->exec("
				UPDATE	BC_CATEGORY_ENV_RESTORE 
				SET		RESTORE_METHOD = '$post_method',
						RESTORE_S_TIME = '$s_period',
						RESTORE_E_TIME = '$e_period',
						EDIT_DATETIME = '$cur_time',
						EDIT_USER_ID = '$user_id'
				WHERE	CATEGORY_ID = $c_id
			");
		}
	}
	
	echo json_encode(array(
			'success' => true,
			'query' => $query,
			'msg' => '수정이 완료되었습니다'
	));
	
} catch(Exception $e) {
		$msg = $e->getMessage();
		
		echo json_encode(array(
			'success' => false,
			'msg' => $msg,
			'lastquery' => $db->lastquery
	));
} 
?>
