<?php
/**
 * 프로그램 API
 */
$rootDir = dirname(dirname(dirname(__DIR__)));
if (!defined("DS")) {
    define("DS", DIRECTORY_SEPARATOR);
}
    
require_once($rootDir . DS . "vendor" . DS ."autoload.php");

use Proxima\core\Response;
use Proxima\core\ApiRequest;
use ProximaCustom\services\CasService;

$api = new ApiRequest();
// 프로그램 조회
$api->get(function ($params) {
    $broadDate = strip_date($params['broad_date']);

    $service = new CasService();
    $result = $service->getPgms($params['channel_code'], $broadDate, $params['pgm_group'] ?? null);
    $pgmInfoList = $result['result']['pgmInfoList'] ?? null;
    $data = [];
    foreach ($pgmInfoList as $pgmInfo) {
        $time = new \Carbon\Carbon($pgmInfo['bdStDtm']);
        $pgmInfo['bdStDtm'] = $time->format('Y-m-d H:i:s');
        $time = new \Carbon\Carbon($pgmInfo['bdEdDtm']);
        $pgmInfo['bdEdDtm'] = $time->format('Y-m-d H:i:s');
        $data[] = $pgmInfo;
    }
    Response::echoJsonOk($data);
});

$api->run();
