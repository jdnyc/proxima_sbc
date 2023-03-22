<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$user_id = $_SESSION['user']['user_id'];
$limit = $_POST['limit'];
$start = $_POST['start'];
$search = '';
$search_date = '';

$is_admin = $_SESSION['user']['is_admin'];
if($is_admin == 'Y')
{
	$user_check = '';
}
else
{
	$user_check = "and c.reg_user_id='$user_id'";
}


if(empty($limit)){
    $limit = 10;
	$start = 0;
}

try
{
//	if(!empty($_POST['search']))//제목 검색
//	{
//		$search = $_POST['search'];
//		$search_field = " and notice_title like '%$search%'";
//	}
//
//	if(!empty($_POST['start_date']))//날짜 검색
//	{
//		$start_date = $_POST['start_date'];
//		$end_date = $_POST['end_date'];
//
//		$search_date = ' and created_date between '.$start_date.' and '.$end_date;
//	}

	$query = "
	select c.* ,
		udc.ud_content_title,
		uv.usr_meta_value
	from BC_USR_META_VALUE uv , 
		bc_content c, 
		bc_ud_content udc 
	where 
		( uv.USR_META_FIELD_ID='682' or uv.USR_META_FIELD_ID='683' or uv.USR_META_FIELD_ID='684' )
		and c.ud_content_id=udc.ud_content_id 
		and c.is_deleted='N' 
		and c.status > 0 
		$user_check 	
		and ( uv.usr_meta_value != '0' and uv.usr_meta_value is not null )
		and c.content_id=uv.content_id
			";
	$order_q = " order by c.created_date desc";

	$total = $db->queryOne(" select count(*) from ( $query ) cnt ");

	$db->setLimit($limit, $start);//notice 공지사항 테이블의 값 불러오기
	$list = $db->queryAll($query.$order_q);
	

	$data = array(
		'success'	=> true,
		'data'		=> $list,
		'total'		=> $total
	);
	echo json_encode($data);
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}
?>