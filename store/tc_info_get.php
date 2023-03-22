<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/functions.php';


try
{
	$content_id = $_POST['content_id'];
	$list_no = $_POST['list_no'];
	$task_id = $_POST['task_id'];
	$tc_type = $_POST['tc_combo'];

	$query = "select * from tc_list
		where content_id='".$content_id."'
		order by tc_in, tc_out";

	$total = $db->queryOne("select count(*) from ($query) cnt");
	$arr_data = $db->queryAll($query);

	$data = array();
	foreach($arr_data as $d) {
		$d['tc_name'] = $d['content_id'].'_'.$d['tc_no'];

		array_push($data, $d);
	}

	die ( json_encode(array(
		'success' => true,
		'total' => $total,
		'data' => $data
	)));
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}


?>