<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/db.php';
require_once 'topic/lib.php';

$user_id = $_SESSION['user']['user_id'];

try
{
	if( empty($user_id) || $user_id=='temp') throw new Exception("로그인해주세요.");
	$category_id	= $_POST['category_id'];
	$parent_category_id	= $_POST['parent_category_id'];
	$broad_date		= $_POST['broad_date'];
	$expired_date	= $_POST['expired_date'];
	$topic			= $_POST['topic'];
	$contents		= $_POST['contents'];

	$modified_time = new DateTime($expired_date);
	$modified_time = $modified_time->format('YmdHis');

	$topic = $db->escape($topic);
	$contents = $db->escape($contents);
	$expired_date_info = new DateTime($expired_date);
	$expired_date = $expired_date_info->format('YmdHis');
	if( $broad_date != '' ) {
		$broad_date_info = new DateTime($broad_date);
		$broad_date = $broad_date_info->format('YmdHis');
	}

	if( $category_id == '' ) {
		$category_id = getSequence('SEQ_BC_CATEGORY_ID');
		$bc_insert = "insert into BC_CATEGORY
				(CATEGORY_ID ,PARENT_ID, CATEGORY_TITLE, SHOW_ORDER ,NO_CHILDREN )
			values
				('".$category_id."','".$parent_category_id."','".$topic."', '".$category_id."', 1)";
		$db->exec($bc_insert);
		//토픽추가시 기본 승인(accept)상태로. 추후 권한설정에따라 바뀔 수 있음.
		$bct_query = "insert into BC_CATEGORY_TOPIC
				(CATEGORY_ID,EXPIRED_DATE,BROAD_DATE,CONTENTS,REQ_USER_ID,STATUS)
			values
				('".$category_id."','".$expired_date."','".$broad_date."','".$contents."','".$user_id."','accept')";
		$db->exec($bct_query);
	} else {
		$bc_update = "update BC_CATEGORY set
				category_title='".$topic."'
			where category_id='".$category_id."'";
		$db->exec($bc_update);
		$bct_query = "update BC_CATEGORY_TOPIC set
				expired_date='".$expired_date."',
				broad_date='".$broad_date."',
				contents='".$contents."'
			where category_id='".$category_id."'";
		$db->exec($bct_query);
	}

	$description = $category_id.' 수정';
	$action = 'topic edit';

	$node_data = $db->queryRow("
		SELECT
			A.CATEGORY_TITLE, A.NO_CHILDREN, B.*
		FROM
			BC_CATEGORY A, BC_CATEGORY_TOPIC B
		WHERE A.CATEGORY_ID=B.CATEGORY_ID
		  AND A.CATEGORY_ID = $category_id
	 ORDER BY A.SHOW_ORDER ASC
	");

	$node = buildNodeFromQuery($node_data);

	echo json_encode(array(
		'success' => true,
		'msg' => $msg,
		'query'=> $query,
		'node' => $node
	));
}
catch ( Exception $e )
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

?>
