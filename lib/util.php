<?php 
function _print($msg){
	echo json_encode(array(
		'success' => false,
		'msg' => $msg
	));
	exit;
}
?>