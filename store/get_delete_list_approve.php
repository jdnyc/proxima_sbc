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
$ud_content_list = $db->queryAll("select ud_content_id, ud_content_title from bc_ud_content");

try
{	
	foreach ($ud_content_list as $ud_content)
	{
		if( !in_array( $ud_content['ud_content_id'], $CG_LIST  ) )
		{
			continue;
		}
		$mappingMetaTable[$ud_content['ud_content_id']] = $ud_content['ud_content_title'];
	}
	
	
	$db->setLimit($limit,$start);

	$query = "
				select 
					d.*,
					c.ud_content_id,
					c.bs_content_id,
					c.title
				from 
					delete_content_list d,
					bc_content c 
				where
					d.content_id = c.content_id 
				and
					c.is_deleted='Y' 
				and
					d.created_date between '".$s_date."' and '".$e_date."'
				";

	$order = " order by d.created_date desc ";

	$del_log = $db->queryAll($query.$order);

	//페이징
	$db->setLimit($limit, $start);

	$delete_list_a = array(
		'success' => true,
//		'total' => $total,
		'delete_list_a' => array()
	);	

	$i = $start+1;
	foreach($del_log as $del)
	{
		if(!in_array($del['ud_content_id'],$CG_LIST))
		{
			continue;
		}
		$type_bs = $db->queryOne("select bs_content_title from bc_bs_content where bs_content_id='{$del['bs_content_id']}'");
		$type_ud = $db->queryOne("select ud_content_title from bc_ud_content where ud_content_id='{$del['ud_content_id']}'");
		$user_nm = $db->queryOne("select user_nm from bc_member where user_id='{$del['user_id']}'");

		array_push($delete_list_a['delete_list_a'], array('id'=>$i,'content_id'=>$del['content_id'], 'user_id'=>$del['user_id'], 'user_nm'=>$user_nm, 'title'=>$del['title'], 'bs_content_title'=>$type_bs, 'ud_content_title'=>$type_ud, 'created_date'=>$del['created_date'],'reason'=>$del['reason']));
		
		$i++;
	}
	
//	$result_email = $mdb->queryOne("select email from bc_member where user_id=$user_id");

		echo json_encode(
			 $delete_list_a
		);
	
}
catch (Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

?>
