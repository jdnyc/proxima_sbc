<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

try
{
	$mode = 'GetDasGenreCategory';
	$data = $_REQUEST;

	$include_return = ClientResultHandleSOAP($mode, $data);
	$include_data = json_decode($include_return['result'], true);

	echo json_encode($include_data);
}
catch(Exception $e)
{
	echo $e->getMessage();
}

?>