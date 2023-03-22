<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

try {
	$contents = json_decode($_POST['contents'], true);
    $user_id = $_SESSION['user']['user_id'];
    $error = empty($_POST['error']) ? 0 : $_POST['error'];

	foreach ($contents as $content) {
		$values = array(
			'content_id' => $content['id'],
			'created_date' => date('YmdHis'),
			'error_count' => $error,
			'is_checked' => $content['is_checked'],
			'last_modify_date' => null,
			'media_id' => null,
			'review_comment' => null,
			'user_id' => $user_id
		);

		$v_cnt = $db->queryOne("
           SELECT	COUNT(*)
		   FROM		BC_MEDIA_QUALITY_INFO
           WHERE	CONTENT_ID = {$values['content_id']}
		   "
		);

		if ($v_cnt > 0){
			$db->exec("
				   UPDATE	BC_MEDIA_QUALITY_INFO
				   SET		IS_CHECKED = '{$values['is_checked']}', USER_ID = '$user_id', ERROR_COUNT = '$error'
				   WHERE	PGM_ID = '$pgm_id'
				   "
			);
		}else{
			$db->exec("
					INSERT INTO BC_MEDIA_QUALITY_INFO
						(CONTENT_ID, CREATED_DATE, ERROR_COUNT, IS_CHECKED, LAST_MODIFY_DATE, MEDIA_ID, REVIEW_COMMENT, USER_ID)
					VALUES
						('" . join("', '", $values) . "')
				   "
			);
		}

		//$db->exec("
			           //MERGE INTO BC_MEDIA_QUALITY_INFO
			                //USING DUAL
			                   //ON (CONTENT_ID = {$values['content_id']})
			    //WHEN MATCHED THEN UPDATE SET IS_CHECKED = '{$values['is_checked']}', USER_ID = '$user_id', ERROR_COUNT = '$error'
			//WHEN NOT MATCHED THEN INSERT (CONTENT_ID, CREATED_DATE, ERROR_COUNT, IS_CHECKED, LAST_MODIFY_DATE, MEDIA_ID, REVIEW_COMMENT, USER_ID)
									//VALUES ('" . join("', '", $values) . "')
		//");
	}

	echo json_encode(array(
		'success' => true,
		'msg' => 'QC가 확인되었습니다.'
	));
} catch (Exception $e) {
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

