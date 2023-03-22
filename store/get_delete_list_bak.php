<?php
session_start();
header("Content-type: application/json; charset=UTF-8");


require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$limit =		$_POST['limit'];
$start =		$_POST['start'];
$s_date =		$_POST['start_date'];
$e_date =		$_POST['end_date'];
$index =		$_POST['index'];
$search_val	=$_POST['search_val'];

if(empty($limit))
{
    $limit = 100;
}

try
{
	//삭제정보 외 콘텐츠 정보 , 유저정보 포함
	$query = "
	select 
		d.* ,
		m.user_nm,
		c.title,
		c.ud_content_title,
		c.bs_content_title
	from 
		delete_content_list d ,
		view_content c,
		bc_member m  
	where
		d.content_id=c.content_id 
	and
		d.user_id=m.user_id 
	and
		c.ud_content_id>4000300
	";

	$query1 = "
	select 
		d.* ,
		m.user_nm,
		c.title,
		c.ud_content_title,
		c.bs_content_title
	from 
		delete_content_list d ,
		view_content c,
		bc_member m  
	where
		d.content_id=c.content_id 
	and
		c.ud_content_id>4000300
	and
		d.user_id=m.user_id
	and
		d.created_date 
	between '".$s_date."' and '".$e_date."'
	";

	//정렬 오더
	$order = " order by d.created_date desc ";
	
	//전체
	$total = $db->queryOne("select count(*) from ( ".$query." ) cnt ");
	

	//페이징
	$db->setLimit($limit, $start);
	$result = $db->queryAll($query1.$order);
	

//	$result_email = $mdb->queryOne("select email from bc_member where user_id=$user_id");

		echo json_encode(array(
			'success' => true,
			'total' => $total,
			'data' => $result
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
