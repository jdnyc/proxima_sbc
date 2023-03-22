<?php
try
{
	$_where = array();
	$search_q = $_POST['search_q'];
//	$search_q = preg_replace("/[#\&\+\-%@=\/\\\:;,\.'\"\^`~\_|\!\?\*$#<>()\[\]\{\}\s]/i", "", $search_q);
	$q_meta_table_id = $_POST['meta_table_id'];
	$status_filter = $_POST['status_filter'];

	$_where [] = "  c.status >= 2  ";
	switch($status_filter)
	{
		case 1:
		break;
		case 2:
			$_where [] = " c.manager_status like '%accept%' ";
		break;
		case 3:
			$_where [] = " c.manager_status like '%decline%' ";
		break;
		case 4:
			$_where [] = " c.manager_status like '%regist%' ";
		break;
		default:
	}


	$_where [] = " c.is_deleted='N' ";


	if(!empty( $_POST['filter_value'] ))
	{
		$_where [] = " c.CATEGORY_FULL_PATH like '{$_POST['filter_value']}%' ";
	}

	$where = join(' and ' , $_where);

	$arr_query = make_db_search_query($search_q, $q_meta_table_id);

	$search_query = $arr_query['search_query'];

	///echo $search_query;
	//file_put_contents(LOG_PATH.'/A_search_query_'.date('Ymd').'.html', date("Y-m-d H:i:s\t")."-	search_query \r\n".$search_query."\r\n", FILE_APPEND);
	$count_query = $arr_query['count_query'];

//	echo $search_query ;
//	exit;
	$query = "select distinct ud_content_id, count(*) as cnt from view_content c,( $search_query ) v where c.content_id=v.content_id ";

	$query .= ' and '.$where;
	$query .= ' group by ud_content_id ';

	$count_list = $db->queryAll( $query );
	$contents = array();
	foreach($count_list as $con)
	{
		$contents[$con['ud_content_id']]['cnt'] = $con['cnt'];
	}

	$query2 = "select u.user_id, u.read_date, c.content_id, c.ud_content_id, c.created_date, c.reg_user_id
		from view_content c,
		  (select * from check_new_by_user where user_id='".$user_id."') u
		where c.content_id=u.content_id(+) ";
	$query2 .= ' and '.$where;

	$content_list = $db->queryAll( $query2 );
	$contents = fetchMetadataForNew($content_list, $contents);

	die(json_encode(array(
		'success' => true,
		'results' => $contents,
		'cs_query' => $query,
		'cs_query2' => $query2
	)));
}
catch(Exception $e)
{
	die(json_encode(array(
		'success' => false,
		'msg' => $e->getMessage() . '(' . $db->last_query . ')'
	)));
}
?>