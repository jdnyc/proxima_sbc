<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/store/get_content_list/libs/functions.php');

$user_id = $_SESSION['user']['user_id'];
$action = $_POST['action'];
$start = empty($_POST['start']) ? 0 : $_POST['start'] ;
$limit = empty($_POST['limit']) ? 20 : $_POST['limit'] ;

try
{

	$query = " select
	*
	from nps_work_list  where work_type='shutdown' and status!='accept' ";

	$order = " order by created_date desc ";

	$where = " ";

	$total = $db->queryOne(" select count(*) from ( ".$query.$where." ) cnt ");

	$db->setLimit($limit, $start);
	$data = $db->queryAll($query.$where.$order);

	foreach( $data as $key => $val )
	{
		$data[$key]['from_user_nm'] = $db->queryOne("select user_nm from bc_member where user_id='{$val['from_user_id']}' ");
		$data[$key]['category_title'] = $db->queryOne("select category_title from bc_category where category_id='{$val['category_id']}' ");
		$data[$key]['program'] = $db->queryOne("select c.category_title from path_mapping p,bc_category c where p.category_id=c.category_id and  p.member_group_id='{$val['member_group_id']}' ");
	}



	echo json_encode(array(
		'success' => true,
		'data' => $data,
		'total' => $total
	));

}
catch (Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}