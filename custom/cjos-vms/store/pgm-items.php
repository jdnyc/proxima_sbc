<?php
/**
 * 프로그램 상품 API
 */
$rootDir = dirname(dirname(dirname(__DIR__)));
if (!defined("DS")) {
    define("DS", DIRECTORY_SEPARATOR);
}
    
require_once($rootDir . DS . "vendor" . DS ."autoload.php");

use Proxima\core\WebPath;
use Proxima\core\Response;
use Proxima\core\ApiRequest;
use ProximaCustom\services\CasService;

$api = new ApiRequest();
// 프로그램 상품 조회
$api->get(function ($params) {
    $broadDate = strip_date($params['broad_date']);

    $service = new CasService();
    $result = $service->getPgmItems($broadDate, $params['channel_code'], $params['pgm_code']);
    $items = $result['result']['itemInfoList'] ?? null;
    $data = [];
    foreach ($items as $item) {
        // 영구중단인 상품 제외
        if ($item['slCls'] === 'D') {
            continue;
        }
        $item['slClsNm'] = $service->getItemStatusName($item['slCls']);
        if (!empty($item['imageUrl'])) {
            // 이미지 url 이 //qa-itemimage.cjmall.net/goods_images/55/278/55199278L.jpg 형식이라 앞이 프로토콜을 붙여야 함
            $item['imageUrl'] = WebPath::getDefaultProtocol() . $item['imageUrl'];
        }
        $data[] = $item;
    }
    Response::echoJsonOk($data);
});

$api->run();
