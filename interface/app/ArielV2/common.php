<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/MetaData.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/soap/nusoap.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/Search.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")."\r\n".'aaa'."\r\n", FILE_APPEND);

$namespace = 'urn:'.substr($_SERVER['SCRIPT_NAME'], 1, (strrpos($_SERVER['SCRIPT_NAME'], '.')-1));

$root_path = $_SERVER['DOCUMENT_ROOT']."/lib/wsdl/";

$server = new soap_server;
$server->soap_defencoding = 'UTF-8';
$server->decode_utf8 = false;
$server->configureWSDL('Common', $namespace);

require_once $_SERVER['DOCUMENT_ROOT'].'/interface/app/actions/GetIngestSchedule.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/interface/app/actions/SetQueuedIngestScheduleItem.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/interface/app/actions/SoapGetNewCartridgeId.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/interface/app/actions/SoapGetTaskId.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/interface/app/actions/SoapUpdateTaskStatus.php';

// 아카이브
require_once $_SERVER['DOCUMENT_ROOT'].'/interface/app/actions/archive/ArchiveAccept.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/interface/app/actions/archive/ArchiveReject.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/interface/app/actions/archive/Restore.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/interface/app/actions/archive/RestoreCopyToNearline.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/interface/app/actions/task/ExecuteTask.php';

// 모니터링 업데이트
require_once $_SERVER['DOCUMENT_ROOT'].'/interface/app/actions/monitor/resource.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/interface/app/actions/monitor/storage.php';

// SGL 커넥터가 종료되었다가 실행되었을 경우에 대한 처리
include_once('Active.php');
$server->register('Active',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#Active',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'Active'
);

// 작업 상태 업데이트
include_once('updateStatus.php');
$server->register('updateStatus',
	array(
		'request'	=> 'xsd:string',
		'request_id'=> 'xsd:int'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#updateStatus',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'updateStatus'
);

//파일러에서 로그인
include_once('login.php');
$server->register('login',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#login',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'login'
);

include_once('insertMetadata.php');
$server->register('insertMetadata',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#insertMetadata',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'insertMetadata'
);

include_once('getMetadata.php');
$server->register('getMetadata',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#getMetadata',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'getMetadata'
);

include_once('downloadInfoContent.php');
$server->register('downloadInfoContent',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#downloadInfoContent',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'downloadInfoContent'
);

include_once('getContentList.php');
$server->register('getContentList',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#getContentList',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'getContentList'
);

include_once('getContentInfo.php');
$server->register('getContentInfo',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#getContentInfo',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'getContentInfo'
);

include_once('getRundownlist.php');
$server->register('getRundownlist',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#getRundownlist',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'getRundownlist'
);

include_once('getRundowndownload.php');
$server->register('getRundowndownload',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#getRundowndownload',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'getRundowndownload'
);


include_once('CueSheet.php');
$server->register('CueSheet',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#CueSheet',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'CueSheet'
);

// 큐시트 목록 조회
include_once('getCueSheetList.php');
$server->register('getCueSheetList',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#getCueSheetList',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'getCueSheetList'
);

// 큐시트 상세목록 조회
include_once('getCueSheetItemList.php');
$server->register('getCueSheetItemList',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#getCueSheetItemList',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'getCueSheetItemList'
);

include_once('insertCueSheet.php');
$server->register('insertCueSheet',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#insertCueSheet',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'insertCueSheet'
);

include_once('updateStatus.php');
$server->register('updateStatus',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#updateStatus',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'updateStatus'
);

include_once('getCode.php');
$server->register('getCode',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#getCode',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'getCode'
);

include_once('getProgramList.php');
$server->register('getProgramList',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#getProgramList',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'getProgramList'
);

// 인제스트 스케쥴 조회
//include_once('getIngestSchedule.php');
//$server->register('getIngestSchedule',
//	array(
//		'request'	=> 'xsd:string'
//	),
//	array(
//		'response'	=> 'xsd:string'
//	),
//	$namespace,					// namespace
//	$namespace.'#getIngestSchedule',		// soapaction
//	'rpc',						// style
//	'encoded',					// use
//	'getIngestSchedule'
//);

// 인제스트 스케쥴 등록
include_once('insertIngestSchedule.php');
$server->register('insertIngestSchedule',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#insertIngestSchedule',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'insertIngestSchedule'
);

// 인제스트 스케쥴 등록전 프로그램 조회
include_once('getIngestProgramList.php');
$server->register('getIngestProgramList',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#getIngestProgramList',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'getIngestProgramList'
);

// NPS to CMS. 파일 보내고 완료 후 메타를 보낸다.
include_once('RequestNPStoCMS.php');
$server->register('RequestNPStoCMS',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#RequestNPStoCMS',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'RequestNPStoCMS'
);

// Zodiac에서 콘텐츠 송출
include_once('RequestTransmissionContent.php');
$server->register('RequestTransmissionContent',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#RequestTransmissionContent',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'RequestTransmissionContent'
);

// Zodiac에서 송출 상태 조회
include_once('gettTransmissionStatus.php');
$server->register('gettTransmissionStatus',
	array(
		'request'	=> 'xsd:string'
	),
	array(
		'response'	=> 'xsd:string'
	),
	$namespace,					// namespace
	$namespace.'#gettTransmissionStatus',		// soapaction
	'rpc',						// style
	'encoded',					// use
	'gettTransmissionStatus'
);

// $server->wsdl->schemaTargetNamespace = $_SERVER['SCRIPT_URI'];

$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : file_get_contents('php://input');

InterfaceClass::_LogFile('','HTTP_RAW_POST_DATA',$HTTP_RAW_POST_DATA);

//$logger->pushHandler(new RotatingFileHandler(BASEDIR . '/log/' . substr(basename(__FILE__), 0, strrpos(basename(__FILE__), '.')) . '.log', 14));
//$logger->addInfo($HTTP_RAW_POST_DATA);

//echo $HTTP_RAW_POST_DATA;
$server->service($HTTP_RAW_POST_DATA);
?>
