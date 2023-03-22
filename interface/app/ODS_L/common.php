<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/MetaData.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/soap/nusoap.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/Search.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

$namespace = 'urn:'.substr($_SERVER['SCRIPT_NAME'], 1, (strrpos($_SERVER['SCRIPT_NAME'], '.')-1));

$root_path = $_SERVER['DOCUMENT_ROOT']."/lib/wsdl/";

$server = new soap_server;
$server->soap_defencoding = 'UTF-8';
$server->decode_utf8 = true;
$server->configureWSDL('Common', $namespace);

// 아카이브
/*
require_once $_SERVER['DOCUMENT_ROOT'].'/interface/app/actions/archive/ArchiveAccept.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/interface/app/actions/archive/ArchiveReject.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/interface/app/actions/archive/Restore.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/interface/app/actions/archive/RestoreCopyToNearline.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/interface/app/actions/task/ExecuteTask.php';
*/

include_once('SoapUpdateTaskStatus.php');
$server->register('SoapUpdateTaskStatus',
	array(
			'task_id' => 'xsd:string',
			'cartridge_id' => 'xsd:string',
			'content_id' => 'xsd:string',
			'progress' => 'xsd:string',
			'status' => 'xsd:string'
	),
	array(
			'code' => 'xsd:string',
			'msg' => 'xsd:string'
	),
	$namespace,
	$namespace.'#SoapUpdateTaskStatus',
	'rpc',
	'encoded',
	'SoapUpdateTaskStatus'
);

include_once('Ariel_ReceiveRequestInfo.php');
$server->register('Ariel_ReceiveRequestInfo',
	array(
			'aValue' => 'xsd:string'
	),
	array(
			'code' => 'xsd:string',
			'msg' => 'xsd:string'
	),
	$namespace,
	$namespace.'#Ariel_ReceiveRequestInfo',
	'rpc',
	'encoded',
	'Ariel_ReceiveRequestInfo'
);

$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : file_get_contents('php://input');

InterfaceClass::_LogFile('','HTTP_RAW_POST_DATA',$HTTP_RAW_POST_DATA);

//$logger->pushHandler(new RotatingFileHandler(BASEDIR . '/log/' . substr(basename(__FILE__), 0, strrpos(basename(__FILE__), '.')) . '.log', 14));
//$logger->addInfo($HTTP_RAW_POST_DATA);

$server->service($HTTP_RAW_POST_DATA);
?>
