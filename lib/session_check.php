<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
session_start();

$v_return = 'true';

try
{
	//file_put_contents(LOG_PATH.'/session_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")."\r\n".$_SESSION['user']['user_id']."\r\n", FILE_APPEND);
	
	if(empty($_SESSION) ||
			empty($_SESSION['user']) ||
			empty($_SESSION['user']['user_id']) ||
			$_SESSION['user']['user_id'] == 'temp'){
		$v_return = 'false';
	}
	
	//file_put_contents(LOG_PATH.'/session_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")."\r\n".$v_return."\r\n", FILE_APPEND);
	
	echo $v_return;
}
catch (Exception $e)
{
	echo 'false';
}