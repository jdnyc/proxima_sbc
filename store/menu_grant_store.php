<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
fn_checkAuthPermission($_SESSION);

$data = array();

try
{
	/*{name: 'ud_content_id' },
	   {name: 'member_group_id'},
	   {name: 'category_id' },
	   {name: 'group_grant'}*/

		$grant_type =$_POST['grant_type'];

		 $top_menu_title = array();
			  
		   $query = "select * from bc_top_menu";
		   $r = $db->queryAll($query);

		   foreach($r as $d)
		   {
				$top_menu_title[$d['id']] = $d['menu_name'];
		   }

		 //  print_r($top_menu_title);
		   

		   $query = "select 						 
              (select member_group_name from bc_member_group where member_group_id = bcmg.member_group_id) group_title,
              bcmg.*
              from bc_top_menu_grant bcmg";

		   $grant_list = $db->queryAll($query);

		   foreach($grant_list as $g_list)
		   {				
				$group_name = $g_list['group_title'];
				$menu_title = $g_list['menu_title'];
				$menu = $g_list['menu_id'];
				$member_group_id = $g_list['member_group_id'];
				
				$menu = explode(',',$menu);
				$menu_str ="";

				foreach($menu as $m)
				{					
					$menu_str .= $top_menu_title[$m]." / ";					
				}
				$menu_str = rtrim($menu_str," / ");

			
				array_push($data, array(					
					$group_name,
					$g_list['menu_id'],
					$member_group_id,
					$menu_str							
				));				
		   }	



		echo json_encode($data);

}
catch (Exception $e)
{
	echo '����: '.$e->getMessage();
}
?>