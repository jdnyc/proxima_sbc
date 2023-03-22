<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$limit = $_POST['limit'];
$start = $_POST['start'];

$s_date = $_POST['start_date'];
$e_date = $_POST['end_date'];

if(empty($limit))
{
	$limit = 50;
}

//순위 /타입  / 파일명 / 등록자 / 조회일자
//  1   movie      2       3	  2010/02/11

//$CG_LIST
$temp_array = array();

foreach($CG_LIST as $cg)
{
	$temp_array [] = " l.ud_content_id='$cg' ";
}
$ud_list = implode(' or ', $temp_array );


$query = "select a.action, a.user_id log_user_id, m.user_nm log_usernm, a.created_date log_date, c.* from 
			(
				select l.action, l.user_id, l.created_date, l.content_id
				from bc_log l
				where 
				 l.action='read'
				and ( $ud_list )
			) a,
		view_content c,
		bc_member m
	where 
		c.content_id=a.content_id
		and m.user_id=a.user_id ";

$order = " order by a.created_date desc ";

$totalquery = " select count(*) from ( $query ) cnt ";



$total = $db->queryOne($totalquery);

$db->setLimit($limit,$start);
$list = $db->queryAll($query.$order);



$array = array();
$i=$start+1;
foreach($list as $l)
{
	$l['inx']= $i; 
	array_push($array , $l);
	$i++;
}
echo json_encode(array(
	'success' => true,
	'total' => $total,
	'read_rank' => $array
));
?>