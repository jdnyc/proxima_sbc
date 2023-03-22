<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
session_start();
fn_checkAuthPermission($_SESSION);

	$user_id = $_SESSION['user']['user_id'];
	$action = $_POST['action'];
	$arr_filter_id = json_decode($_POST['arr_filter_id']);
	// $cur_datetime = date('YmdHis');
	//$content_id = $_POST['content_id'];
	// $tag_category_id = $_POST['tag_category_id'];
	
	switch($_POST['action']){
		
		case 'listing':
			$query = "	SELECT ID, trim(title) as title, trim(user_id) as user_id, use_yn, trim (code) as code
						FROM bc_user_filters
						WHERE USER_ID = '$user_id'
						ORDER BY ID ASC
					";
			$user_filter_list = $db->queryAll($query);
			$data = $user_filter_list;
		break;
		
		case 'get_list_using':
			$query = "	SELECT ID, trim(title) as title, trim(user_id) as user_id, use_yn, trim (code) as code
						FROM bc_user_filters
						WHERE USER_ID = '$user_id'
						AND USE_YN = 'Y'
						ORDER BY ID ASC
					";
			$user_filter_list = $db->queryAll($query);
			$data = $user_filter_list;
      	break;
		case 'remove_filter':

			foreach ($arr_filter_id as $filter_id){
				
				$query = "UPDATE bc_user_filters SET USE_YN = 'N' WHERE id = $filter_id AND USER_ID = '$user_id'";
				$db->exec($query);
				$data = 'update use_yn N';
			}
		break;
		
		case 'update_useyn_filter':
			$arr_filter_selection = json_decode($_POST['arr_filter_selection']);
			$arr_filter_unselection = json_decode($_POST['arr_filter_unselection']);

			foreach ($arr_filter_selection as $filter_id){
				$query = "UPDATE bc_user_filters SET USE_YN = 'Y' WHERE id = $filter_id AND USER_ID = '$user_id'";
				$db->exec($query);
			}

			foreach ($arr_filter_unselection as $filter_id){
				$query = "UPDATE bc_user_filters SET USE_YN = 'N' WHERE id = $filter_id AND USER_ID = '$user_id'";
				$db->exec($query);
			}

			$data = 'update use_yn';
		break;

		default:
			throw new Exception ('알수 없는 action 입니다');
		break;
	}

	echo json_encode(array(
		'success' => true,
		'data' => $data,
		'action' => $action
	));
?>