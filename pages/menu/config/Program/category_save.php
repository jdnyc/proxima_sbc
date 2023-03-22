<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/ActiveDirectory.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

$action = $_POST['action'];
$category_id = $_POST['category_id'];
$category_name = $_POST['category_name'];
$folder = $_POST['folder'];

$storage_size = $_POST['quota'];
$using_review = $_POST['using_review'];
$ud_storage = $_POST['ud_storage'];
$owner_user_id = empty( $_SESSION['user']['user_id'] ) ? 'admin': $_SESSION['user']['user_id'];


try
{
	$codeList = getCodeInfo( 'STORAGE_ROOT'  );
	if( empty($codeList) ) throw new Exception("루트 스토리지 정보가 없습니다.");
	$codeList = array_shift($codeList);
	$root_storage_id =  $codeList['code'];
	$root_storage = $db->queryRow("select * from bc_storage where storage_id='$root_storage_id'");
	if( empty($root_storage) ) throw new Exception("루트 스토리지 정보가 없습니다.");

	$win_root = $root_storage['path'];
	$unix_root = $root_storage['path_for_unix'];

	$AD = new ActiveDirectory();


//	$db->setTransaction(true);

	if( $action=='add' ){
		$params = json_decode($_POST['params'], true);
/*		$param = array(
			'category_name' => $category_name,
			'group_name' => $folder,
			'win_root_path' => $win_root,
			'directory' => $folder,
			'max_size' => $storage_size,
			'unix_root_path' => $unix_root
		);
		$result = $AD->CreateGroup( $param );
		$result_array = json_decode($result, true);
		if($result_array[status] == '0'){
*/			$category_id = getSequence('SEQ_BC_CATEGORY_ID');
			$category_name = $db->escape($category_name);
			$size_byte = $storage_size;

			$insert_q = "insert into BC_CATEGORY (CATEGORY_ID ,PARENT_ID, CATEGORY_TITLE, SHOW_ORDER ,NO_CHILDREN ) values ($category_id,0,'$category_name', '$category_id', 1)";
			$r = $db->exec($insert_q);

			//패스 매핑 추가
			$insert_q="insert into PATH_MAPPING (CATEGORY_ID,PATH,QUOTA,USAGE ) values ($category_id,'$folder', '$size_byte', '0' )";
			$r = $db->exec($insert_q);
/*		}else{
			throw new Exception($result_array[message]);
		}
*/
	}
	if($action=='edit'){
//		$params = json_decode($_POST['params'], true);
//
		$category_id = $_POST['category_id'];
//
//		$category_name = $db->escape($_POST['category_name']);
//		$folder = $_POST['folder'];
//
		$size_byte = $storage_size ;

		$param = array(
			'directory' => $folder,
			'max_size' => $storage_size,
			'unix_root_path' => $unix_root
		);

		$result = $AD->EditQUOTA( $param );
		$result_array = json_decode($result, true);
		if($result_array[status] == '0'){
				$edit_categories="update BC_CATEGORY set CATEGORY_TITLE='$category_name' where category_id='$category_id'";
				$r = $db->exec($edit_categories);
				$insert_q="update PATH_MAPPING set QUOTA='$size_byte'  where category_id= '$category_id'";
				$r = $db->exec($insert_q);
		}else{
			throw new Exception($result_array[message]);
		}
	}

	if($action=='del')
	{
		$category_id = $_POST['category_id'];
		$category_info = $db->queryRow("select * from BC_CATEGORY where category_id='$category_id'");
		$path_info = $db->queryRow("select * from PATH_MAPPING where category_id='$category_id'");
/*
		$param = array(
			'group_name' => $path_info['path'],
			'category_name' => $category_info['category_title'],
			'common_name' => $path_info['path'],
			'win_root_path' => $win_root,
			'directory' => $path_info['path'],
			'max_size' => $path_info['quota'],
			'unix_root_path' => $unix_root
		);
		$result = $AD->DeleteGroup( $param );
		$result_array = json_decode($result, true);
		if($result_array[status] == '0'){
*/			$del_categories="delete from BC_CATEGORY where category_id=$category_id";
			$r = $db->exec($del_categories);
			$del_path = "delete from PATH_MAPPING where category_id=$category_id";
			$r = $db->exec($del_path);
			$del_path="delete from user_mapping where category_id=$category_id";
			$r = $db->exec($del_path);
/*		}else{
			throw new Exception($result_array[message]);
		}
*/
	}

	echo json_encode(array(
		'success' => true,
		'msg' => $result
	));

//	$db->commit();

}
catch(Exception $e)
{

//	$db->rollback();

	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

?>