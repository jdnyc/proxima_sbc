<?php
/**
 * 상품 API
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
// 상품 조회
$api->get(function ($params) {
    $mockData = [
        'itemCd' => '50339358',
        'itemNm' => '[스킨푸드] 피치뽀송 멀티 피니시 파우더소용량]',
        'chnCd' => '30001001',
        'slCls' => '정상'
    ];

    $data = $mockData;
    Response::echoJsonOk([$data]);
});

$api->run();
