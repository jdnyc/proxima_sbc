<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/ActiveDirectory.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

$owner_user_id = $_SESSION['user']['user_id'];

$category_user_id	= $_POST['category_user_id'];
$user_id 			= $_POST['user_id'];
//$category_id		= $_POST['category_id'];
$action				= $_POST['action'];

try
{

//	$db->setTransaction(true);
	$AD = new ActiveDirectory();

	if ($action=='add')
	{
		$category_ids = json_decode($_POST['category_id']);
		foreach($category_ids as $category_id)
		{
			$users = json_decode($_POST['user_id']);
			$category_info = $db->queryRow("select * from path_mapping where category_id='$category_id'");

			$param_array = array();

			foreach($users as $user_id)
			{
				$dupl_name = $db->queryOne("select m.user_nm name from user_mapping um, bc_member m where m.user_id = um.user_id and um.user_id='$user_id' and um.category_id='$category_id'");

				if( empty($dupl_name) )
				{
					$memberInfo = $db->queryRow("select * from bc_member where user_id ='$user_id'");

					$params = array(
						'user_id' => $memberInfo['user_id'],
						'user_name' => $memberInfo['user_id'],
						'group_name' => $category_info['path'],
						'common_name' => $memberInfo['user_nm'].'('.$memberInfo['user_id'].')',
						'password' => $memberInfo['ori_password']
					);

					array_push($param_array, $params);
				}
				else
				{
					throw new Exception($dupl_name.'님은 이미 등록된 사용자입니다.', -5);
				}
			}
/*
			$result = $AD->CreateUser($param_array);
			$result_array = json_decode($result, true);
			if($result_array[status] == '0'){
*/				foreach($users as $user_id)
				{
					$r = $db->exec("insert into USER_MAPPING ( CATEGORY_ID, USER_ID ) values(  '$category_id', '$user_id'  ) ");
				}
/*			}else{
				throw new Exception($result_array[message]);
			}
*/		}
	}
	else if($action=='del')
	{
		$user_ids  = $_POST['user_ids'];
		if( !empty($user_ids) && ($user_id_array = json_decode($user_ids , true) )  )
		{
			$param_array = array();
			foreach($user_id_array as $list)
			{
				$user_id = $list['user_id'];
				$category_id = $list['category_id'];

				$memberInfo = $db->queryRow("select * from bc_member where user_id='$user_id'");
				$category_info = $db->queryRow("select * from path_mapping where category_id='$category_id'");

				$params = array(
					'group_name' => $category_info['path'],
					'common_name' => $memberInfo['user_nm'].'('.$memberInfo['user_id'].')'
				);
				array_push($param_array, $params);


			}

			$result = $AD->DeleteUser($param_array);
			$result_array = json_decode($result, true);
			if($result_array[status] == '0'){
				foreach($user_id_array as $list)
				{
					$r = $db->exec("delete from USER_MAPPING where category_id='$category_id' and user_id='$user_id'");
				}
			}else{
				throw new Exception($result_array[message]);
			}
		}
	}
	else
	{
		throw new Exception('정의되지 않은 액션입니다.', -1);
	}

//	$db->commit();

	echo json_encode(array(
		'success' => true,
		'msg' => '성공'
	));

}
catch (Exception $e)
{

//	$db->rollback();


	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}
?>