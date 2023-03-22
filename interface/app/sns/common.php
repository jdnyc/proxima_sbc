<?php
require_once '../../../vendor/autoload.php';

use Carbon\Carbon;
use Monolog\Handler\RotatingFileHandler;

require_once $_SERVER['DOCUMENT_ROOT'].'/interface/app/common/validator.php';

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/MetaData.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/soap/nusoap.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/Search.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")."\r\n".'aaa'."\r\n", FILE_APPEND);

$namespace = 'urn:'.substr($_SERVER['SCRIPT_NAME'], 1, (strrpos($_SERVER['SCRIPT_NAME'], '.')-1));

$root_path = $_SERVER['DOCUMENT_ROOT']."/lib/wsdl/";

$server = new soap_server;
$server->soap_defencoding = 'UTF-8';
$server->decode_utf8 = true;
$server->configureWSDL('Common', $namespace);


include_once('updateStatusSNS.php');
$server->register('updateStatusSNS',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#updateStatusSNS',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'updateStatusSNS'
);

// $server->wsdl->schemaTargetNamespace = $_SERVER['SCRIPT_URI'];

$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : file_get_contents('php://input');

//InterfaceClass::_LogFile('','HTTP_RAW_POST_DATA',$HTTP_RAW_POST_DATA);

$logger->pushHandler(new RotatingFileHandler(BASEDIR . '/log/' . substr(basename(__FILE__), 0, strrpos(basename(__FILE__), '.')) . '.log', 14));
$logger->addInfo($HTTP_RAW_POST_DATA);

//echo $HTTP_RAW_POST_DATA;
$server->service($HTTP_RAW_POST_DATA);
?>
