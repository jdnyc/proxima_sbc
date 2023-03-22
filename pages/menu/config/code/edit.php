<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$type = $_POST['type'];
try
{
	$data = json_decode($_POST['values'], true);
	$use_yn = empty($data['use_yn']) ? 'N' : 'Y';
	if( $type == 'code' ){
		$data['use_yn'] = $use_yn;
	}
	$where = " id = ".$_POST[$type.'_id']." ";
	$query = $db->update('BC_'.strtoupper(trim($type)), $data,  $where);
	echo json_encode(array(
		'success' => true,
		'query'	=>	 $query,
		'add'		=>	 true
	));
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => _text('MN00022')." : ".$e
	));
}


?>