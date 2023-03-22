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
    $service = new CasService();
    $data = $service->getItemChannels();
    $data = $data['result']['itemChnCdList'] ?? null;
    // 채널명을 채널명 + | + 채널코드 형태로 변경
    for($i = 0; $i<count($data); $i++) {
        $data[$i]['chnNm'] = "{$data[$i]['chnNm']} | {$data[$i]['chnCd']}";
    }
    Response::echoJsonOk($data);
});

$api->run();
