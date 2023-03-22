<?php
session_start();
header("Content-type: application/json; charset=UTF-8");


require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$user_id = trim($_REQUEST['user_id']);

try
{

	
	$fields = $mdb->queryAll("select c.name, c.member_id
								from member_group_member a, member_group b, member c 
								where a.member_group_id=b.member_group_id
								and a.member_id=c.member_id
								and b.member_group_id='4251663'
								order by c.name");
	$i = 1;
	$j = 1;
	foreach ($fields as $field){
		$result[] = array(
			'worker_name'			=> $field['name'],
			'worker_name_index'		=> $i,
			'worker_id_1'				=> $field['member_id'],
			'worker_id_index'		=> $j
			
		);
		$i++;
		$j++;
	}

//	$result_email = $mdb->queryOne("select email from bc_member where user_id=$user_id");

		echo json_encode(array(
			'success' => true,
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
