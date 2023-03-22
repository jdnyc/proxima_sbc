<?php
function HandleError($msg)
{
	die(json_encode(array(
		'success' => false,
		'msg' => $msg
	)));
}

function HandleSuccess($args = null)
{
	die(json_encode(array(
		'success' => true,
		'args' => $args
	)));
}

function error_handler($errno, $errstr, $errfile, $errline)
{
	global $db;
	//$errstr = iconv('EUC-KR', 'UTF-8', $errstr);

//	$msg = date('H:i:s').' '.$errfile."\tline: ".$errline."\terror no: ".$errno.', '.$errstr."\n";
	$msg = date('H:i:s').' '.$errfile."\tline: ".$errline."\terror no: ".$errno.', '.$errstr."\t".$db->last_query."\n";

	file_put_contents(BASEDIR.'/log/error.'.date('Ymd').'.log', $msg, FILE_APPEND);

	HandleError( $msg );
}

set_error_handler('error_handler', E_ALL^E_NOTICE^E_WARNING);
?>