<?php
/**
 * 상품 채널 API
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
// 상품 채널 조회
$api->get(function ($params) {
    $mockData = [
        [
            'chnCd' => '123',
            'chnNm' => '채널1'
        ],
        [
            'chnCd' => '112',
            'chnNm' => '채널2'
        ]
    ];

    $data = json_decode($mockData, true);
    Response::echoJsonOk($data);
});

$api->run();
