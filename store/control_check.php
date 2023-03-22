<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');


if( content_control_check ( $_SESSION['user']['user_id'] , $_POST['content_id']) )
{
	echo json_encode(array(
		'success'	=> true
	));
}
else
{
	echo json_encode(array(
		'success'	=> false
	));
}


?>