<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/functions.php';

$user_id = $_SESSION['user']['user_id'];

/*POST
category_full_path
category_id
group_grant
member_group_id
ud_content_id
*/
try
{
	$action = $_POST['action'];

	$grant_type =$_POST['grant_type'];// 'content_grant';//권한 타입

	switch($_POST['action'])
	{
		case 'add':
			
			$ud_content_ids = array();
			$member_group_ids = array();		

			foreach($_POST as $key => $values)
			{
				$list_array = explode('-', $key);		
				
				if( !( is_array($list_array) && !empty($list_array) && !empty($list_array[1]) ) ) continue;
				//텍스트분할해서 배열이며 두번째 배열이 존재하는것만, 0값도 제외 전체 선택으로 사용

				if ( strstr($key, 'ud_content_id') )
				{
					$ud_content_ids [] = $list_array[1];
				}
				else if( strstr($key, 'member_group_id') )
				{
					$member_group_ids [] =  $list_array[1];
				}						
			}
				
			$category_id =  is_null($_POST['category_id']) ? '0': $_POST['category_id'] ;
			$category_full_path = empty($_POST['category_full_path']) ? '/0': $_POST['category_full_path'];



			if( !( is_array($ud_content_ids) && is_array($member_group_ids) ) ) break;

			$group_grant = $_POST['group_grant'];

		
			
			foreach($ud_content_ids as $ud_content_id)
			{
				foreach($member_group_ids  as $member_group_id)
				{
					$is_exist = $db->queryOne("select * from BC_CATEGORY_GRANT where ud_content_id='$ud_content_id' and member_group_id='$member_group_id' and category_id='$category_id'");

					if( empty($is_exist) )//없으면 추가
					{
						$r = $db->exec("insert into BC_CATEGORY_GRANT ( UD_CONTENT_ID, MEMBER_GROUP_ID, GROUP_GRANT, CATEGORY_ID, CATEGORY_FULL_PATH ) values ('$ud_content_id','$member_group_id','$group_grant','$category_id','$category_full_path')");
					}
					else//있으면 업데이트
					{
						$r = $db->exec("update BC_CATEGORY_GRANT set GROUP_GRANT='$group_grant' where ud_content_id='$ud_content_id' and  member_group_id='$member_group_id' and category_id='$category_id'");
					}
				}
			}

		break;	

		case 'delete':

		if( $lists = json_decode($_POST['list'], true) )
		{
			foreach($lists as  $list)
			{
				$ud_content_id = $list['ud_content_id'];
				$member_group_id = $list['member_group_id'];			
				$category_id =   $list['category_id'] ;			

				$db->exec("delete from BC_CATEGORY_GRANT where ud_content_id='$ud_content_id' and member_group_id='$member_group_id' and category_id='$category_id'");
			}
		}
		break;

		default :
			throw new Exception('알수 없는 액션입니다');
		break;
	}


	echo json_encode(array(
		'success' => true
	));
}
catch ( Exception $e )
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}
?>