<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');


try{
	$action = $_POST['action'];
	$user_id = $_SESSION['user']['user_id'];
	$ud_contents = json_decode($_POST['ud_contents']);
	$now = date('YmdHis');
	
	if(empty($user_id)) {
		throw new Exception(_text('MSG02089'));
	}
	
	if(empty($action)) {
		throw new Exception(text('MSG01022'));
	}
	
	$ud_content_ids = implode(",", $ud_contents);
	
	switch($action) {
		case 'update' :
			// update TB_LOUDNESS_CONFIG for 2times (1st : update to Y / 2nd : update to N)
			// update to N
			$update_n_query = "
					UPDATE	TB_LOUDNESS_CONFIG
					SET		USE_YN = 'N',
							MODIFIED_USER_ID = '$user_id',
							MODIFIED_DATETIME = '$now'
					WHERE	UD_CONTENT_ID NOT IN (".$ud_content_ids.")
			";
			$r1 = $db->exec($update_n_query);

			// update to Y
			foreach($ud_contents as $ud_content) {
				// check TB_LOUDNESS_CONFIG
				$check_row = $db->queryOne("
								SELECT	COUNT(UD_CONTENT_ID)
								FROM	TB_LOUDNESS_CONFIG
								WHERE	UD_CONTENT_ID = $ud_content
							");
				
				if($check_row > 0) { // already existed and do update
					$update_y_query = "
							UPDATE	TB_LOUDNESS_CONFIG
							SET		USE_YN = 'Y',
									MODIFIED_USER_ID = '$user_id',
									MODIFIED_DATETIME = '$now'
							WHERE	UD_CONTENT_ID = $ud_content	
					";
				} else {
					$update_y_query	= "
							INSERT INTO TB_LOUDNESS_CONFIG
								(UD_CONTENT_ID, USE_YN, MODIFIED_USER_ID, MODIFIED_DATETIME)
							VALUES
								($ud_content, 'Y', '$user_id', '$now')
					";
				}
				
				$r2 = $db->exec($update_y_query);
			}
		break;
	}
	
	echo json_encode(array(
			'success' => true,
			'msg'	=> _text('MSG00062')
	));
	
} catch (Exception $e) {
	echo json_encode(array(
			'success' => false,
			'msg' => $e->getMessage(),
			'last_query' => $db->last_query
	));
}
?>