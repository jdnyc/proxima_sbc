<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$user_id = $_SESSION['user']['user_id'];
$name = $_POST['name'];
try
{	
	$user_list = $db->queryAll("
	select
		c.category_id category_id,
		c.category_title title,	
		u.user_id,
		m.user_nm name,
		m.dept_nm,
		m.breake,
		m.dep_tel_num
	from
		bc_category c,
		path_mapping p,
		user_mapping u,
		bc_member m
	where
		p.category_id = c.category_id
	and c.category_id = u.category_id(+)
	and m.user_id=u.user_id and ( m.user_nm like '%$name%' or c.category_title like '%$name%' ) ");


	echo json_encode(array(
		'success' => true,
		'msg' => $user_list
	));

}
catch (Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));	
}