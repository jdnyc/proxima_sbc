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

	if($grant_type == 'top_menu_grant')
	{
		switch($_POST['action'])
		{
			case     'add':
				$member_group_ids = array();
				$menu_list = array();	
				
				foreach($_POST as $key => $values)
				{
					$list_array = explode('-', $key);
					if( !( is_array($list_array) && !empty($list_array) && !empty($list_array[1]) ) ) continue;
					//텍스트분할해서 배열이며 두번째 배열이 존재하는것만, 0값도 제외 전체 선택으로 사용

					if( strstr($key, 'member_group_id') )
					{
						$member_group_ids [] =  $list_array[1];
					}
					else if( strstr($key, 'menu_id') )
					{
						$menu_list [] =  $list_array[1];	
						$menu .= $list_array[1].",";
					}	
				}			
			
				$menu = rtrim($menu,",");	
			
				foreach($member_group_ids as $mg_ids)
				{	
					//print_r($mg_ids);
					$query = "select count(*) from bc_top_menu_grant
											  where member_group_id = {$mg_ids}";
					$r = $db->queryOne($query);

					if($r>0)
					{
						$query = "delete from bc_top_menu_grant where member_group_id = {$mg_ids}";
						$db->exec($query);
					}								  

					$query = "insert into bc_top_menu_grant (menu_id,member_group_id) values ('$menu',$mg_ids)";

					$db->exec($query);		
				
				}				

			break;
			case  'delete':				
			
				foreach(json_decode($_POST['list'],true) as $list)
				{					
					$mg_ids = $list['member_group_id'];
					$query = "delete from bc_top_menu_grant where member_group_id = {$mg_ids}"; 
															
					$db->exec($query);			
				}

			break;			
		}

		echo json_encode(array(
			'success' => true
		));	
		
	}
		
}
catch ( Exception $e )
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}
?>