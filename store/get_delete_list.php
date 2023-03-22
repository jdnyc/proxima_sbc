<?php
session_start();

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$limit =			empty($_POST['limit'])? 100:$_POST['limit'];
$start =		empty($_POST['start']) ? 0:$_POST['start'];
$s_date =		$_POST['start_date'];
$e_date =		$_POST['end_date'];
$index =		$_POST['index'];
$search_val	=$_POST['search_val'];

try
{
	$query = "
				select
					d.*,
					(select user_nm from bc_member where user_id=d.user_id ) user_nm,
					c.bs_content_title,
					c.ud_content_title,
					c.category_title,
					c.category_path,
					c.ud_content_id,
					c.bs_content_id,
					c.title
				from
					delete_content_list d,
					view_bc_content c
				where
					d.content_id = c.content_id
				and
					c.is_deleted='Y'
				and
					d.created_date between ".$s_date." and ".$e_date."
				";
	$order = " order by d.created_date desc ";

	$db->setLimit($limit, $start);
	$del_log = $db->queryAll($query.$order);

	echo json_encode( array(
		'success' => true,
		'data' => $del_log,
		'query' => $query
	));

}
catch (Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

?>
