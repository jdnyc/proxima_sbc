<?php
session_start();
require_once('../lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/store/get_content_list/libs/functions.php');
try
{
	$start = 1;
	$limit = 2;
	$_where = array();
	$search_q = $_POST['search_q'];
	$q_meta_table_id = $_POST['meta_table_id'];

	$_where [] = " c.is_deleted='N' ";
	$_where [] = " c.status =2 ";
	$_where [] = " c.ud_content_id='$q_meta_table_id' ";



	//카테고리 패스있을때
	if( !empty( $_POST['filter_value'] ) )
	{
		if( ( $_POST['filter_value'] == '/0' ) && ( $_SESSION['user']['is_admin']  != 'Y' ) && !in_array($q_meta_table_id, $CG_LIST) )
		{
			$_subwhere = array();
			$user_id = $_SESSION['user']['user_id'];
			$lists = $db->queryAll("select category_id from user_mapping where user_id='$user_id'");

			if( !empty($lists) )
			{
				foreach($lists as $list)
				{
					$category_id = $list['category_id'];
					$_subwhere [] = " c.CATEGORY_FULL_PATH like '/0/{$category_id}%' " ;
				}
				$_where [] = " ( ".join(' or ' ,$_subwhere)." ) " ;
			}
			else
			{//이런건 없을테지만..
				$_where [] = " ( 1!=1 ) " ;
			}
		}
		else
		{
			$_where [] = " c.CATEGORY_FULL_PATH like '{$_POST['filter_value']}%' ";
		}
	}

	if( $_REQUEST['mode'] == 'last' )
	{//제작 프로그램의 부제카테고리 이고 부제가 지난 제작 프로그램일때
		array_push($_where, " ( c.category_code = 'last' and c.PARENT_ID != '0' ) ");
	}
	else
	{
		array_push($_where, " ( ( c.category_code is null  ) or ( c.category_code != 'last'  ) or ( c.category_code = 'last' and c.PARENT_ID = '0' ) ) ");
	}

	$where = join(' and ' , $_where);

	$arr_query = make_db_search_query($search_q, $q_meta_table_id);

	$search_query = $arr_query['search_query'];
	$count_query = $arr_query['count_query'];

	$query = "select c.* from view_content c,( $search_query ) v where c.content_id=v.content_id ";
	$query .= ' and '.$where ;

	$total = $db->queryOne("select count(*) from ( $query ) cnt ");

	echo $total;

}
catch (Exception $e)
{
	echo $e->getMessage();
}
?>