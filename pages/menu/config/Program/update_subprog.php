<?php
set_time_limit(0);
session_start();

define(LROOT, $_SERVER['DOCUMENT_ROOT'] );
require_once(LROOT.'/lib/config.php');
require_once(LROOT.'/mssql_connection.php');

//$category_id = '4164394';
//$params = array(
//	'medcd' => '001',
//	'progparntcd' => '0001966',
//	'prognm' => 'EBS 스페이스 공감(HD)',
//	'brodstymd' => '20110328',
//	'brodendymd' => '20110821',
//	'formbaseymd' => '20110228',
//	'progcd' => '17J0PB0001'
//);

try
{
	$type = $_POST['type'];
	$category_id = $_POST['category_id'];

	if($type == 'all')
	{
		$lists = $db->queryAll("select cp.* from bc_category c, path_mapping p , category_progcd_mapping cp where c.category_id=p.category_id and c.category_id=cp.category_id and c.parent_id=0");

		foreach($lists as $list)
		{
			$category_id = $list['category_id'];
			create_subprog($category_id , $list);
		}
	}
	else if($type == 'each' && !empty($category_id) )
	{
		$lists = $db->queryAll("select cp.* from bc_category c, path_mapping p , category_progcd_mapping cp where c.category_id=p.category_id and c.category_id=cp.category_id and c.parent_id=0 and c.category_id='$category_id' ");

		foreach($lists as $list)
		{
			$category_id = $list['category_id'];
			create_subprog($category_id , $list);
		}
	}
	else
	{
		throw new Exception('알수 없는 정보입니다');
	}

	echo json_encode(array(
		'success' => true,
		'msg' => '제작 프로그램 회차 목록이 생성되었습니다'
	));

}
catch (Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}
//4164394	001	0024897	EBS 스페이스 공감(HD)	20120227		20120227	17J0PB0007
//4164394	001	0001966	EBS 스페이스 공감(HD)	20120130		20110829	17J0PB0001
//4164394	001	0001966	EBS 스페이스 공감(HD)	20110328	20110821	20110228	17J0PB0001

function create_subprog($parent_id , $params)
{
	global $db;
	global $db_ms;

	$medcd = $params['medcd'];
	$progparntcd = $params['progparntcd'];
	$progcd = $params['progcd'];
	$prognm = $params['prognm'];
	$formbaseymd = $params['formbaseymd'];
	$brodstymd = $params['brodstymd'];
	$brodendymd = $params['brodendymd'];

	$forquery = "
		select
			tm2.*,
			tb1.korname
		from
			tbbf002 tf2,
			tbbma02 tm2,
			tbpae01 tb1
		where
			tm2.pdempno=tb1.empno
		and tm2.medcd=tf2.medcd
		and tf2.progcd=tm2.progcd
		and tf2.formbaseymd=tm2.formbaseymd
		and tf2.brodgu='001'
		and tf2.medcd='$medcd'
		and tf2.formbaseymd='$formbaseymd'
		and tf2.progcd='$progcd' order by tm2.subprogcd ";

	$lists = $db_ms->queryAll($forquery);

	foreach($lists as $list)
	{
		$progcd			= $list['progcd'];
		$formbaseymd	= $list['formbaseymd'];
		$subprogcd		= $list['subprogcd'];
		$subprognm		= $db->escape($list['subprognm']);

		$r = $db->exec("update bc_category set NO_CHILDREN='0' where category_id='$parent_id'");

		$check_category_id = $db->queryOne("select category_id from SUBPROG_MAPPING where progcd='$progcd' and formbaseymd='$formbaseymd' and subprogcd='$subprogcd' and parent_id='$parent_id' ");

		if( empty($check_category_id) )
		{
			$category_id = getSequence('SEQ_BC_CATEGORY_ID');

			$insert_q = "insert into SUBPROG_MAPPING (CATEGORY_ID, PARENT_ID, PROGCD, FORMBASEYMD, SUBPROGCD, SUBPROGNM) values ('$category_id', '$parent_id','$progcd','$formbaseymd','$subprogcd','$subprognm' ) ";
			$r = $db->exec($insert_q);
			$category_name =  preg_replace("/[#\&\+%@=\/\\\:;,\.'\"\^`~|\!\?\*$#<>\[\]\{\}\s]/i", "", $category_name);

			$category_name = (int)$subprogcd.'회)'.$category_name;

			$insert_cq = "insert into BC_CATEGORY (CATEGORY_ID ,PARENT_ID, CATEGORY_TITLE, SHOW_ORDER ,NO_CHILDREN ) values ($category_id,'$parent_id','$category_name', '$category_id', 1)";
			$r = $db->exec($insert_cq);
		}
		else
		{
			$category_name = preg_replace("/[#\&\+%@=\/\\\:;,\.'\"\^`~|\!\?\*$#<>\[\]\{\}\s]/i", "", $category_name);
			$category_name = (int)$subprogcd.'회)'.$category_name;

			$show_order1 = $formbaseymd;
			$show_order2 = $subprogcd;
			$show_order = (int)( $show_order1.''.$show_order2 );

			$insert_cq = "update BC_CATEGORY set CATEGORY_TITLE ='$category_name',show_order='".$show_order."' where category_id='$check_category_id' ";
			$r = $db->exec($insert_cq);
		}

	}

}


?>