<?php
function handleError($msg) {
	die(json_encode(array(
		'success' => false,
		'msg' => $msg
	)));
}
?>