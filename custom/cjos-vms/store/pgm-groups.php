<?php
/**
 * 프로그램 그룹 API
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
// 프로그램 그룹 조회
$api->get(function ($params) {
    $service = new CasService();
    $data = $service->getPgmGroups();
    $data = $data['result']['rmPgmGroupCdList'] ?? null;
    Response::echoJsonOk($data);
});

$api->run();
