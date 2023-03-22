<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/MetaData.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/soap/nusoap.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

$namespace = 'urn:'.substr($_SERVER['SCRIPT_NAME'], 1, (strrpos($_SERVER['SCRIPT_NAME'], '.')-1));

$server = new soap_server;
$server->soap_defencoding = 'UTF-8';
$server->decode_utf8 = false;
$server->configureWSDL('TmRequest', $namespace);

include_once('request.php');
$server->register('request',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#request',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'request'
);

include_once('status.php');
$server->register('status',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#status',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'status'
);

#$server->wsdl->schemaTargetNamespace = $_SERVER['SCRIPT_URI'];

$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : file_get_contents('php://input');

InterfaceClass::_LogFile('','HTTP_RAW_POST_DATA_TmRequest',$HTTP_RAW_POST_DATA);

//echo $HTTP_RAW_POST_DATA;
$server->service($HTTP_RAW_POST_DATA);
?>
