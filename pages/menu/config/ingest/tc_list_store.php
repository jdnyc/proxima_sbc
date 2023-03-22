<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

//$user_id = $_SESSION['user']['user_id'];
$id = $_POST['id'];

try
{
	$query = "select * from ingest_tc_list where ingest_list_id ='$id' ";

	$tc_lists = $mdb->queryAll($query);

	$data = array(
		'success'	=> true,
		'data'		=> array()
	);
	foreach($tc_lists as $list)
	{
		array_push($data['data'], array('id'				=> $list['id'],
										'tc_in'				=> $list['tc_in'],
										'tc_out'			=> $list['tc_out']

		));
	}
	echo json_encode($data);
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();

}
?>