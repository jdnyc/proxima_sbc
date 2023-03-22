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
    $mockData = '[{
          "pgmCd": "563156",
          "pgmNm": "T모찌피치 1부",
          "bdStDtm": "2018-05-30 17:48:23",
          "showHostInfo": "이지혜"
        }, {
          "pgmCd": "561234",
          "pgmNm": "T패션 1부",
          "bdStDtm": "2018-05-30 18:48:23",
          "showHostInfo": "김연진, 이나래"
        }]';

    $data = json_decode($mockData, true);
    Response::echoJsonOk($data);
});

$api->run();
