<?php
/**
 * 상품 API
 */
$rootDir = dirname(dirname(dirname(__DIR__)));
if (!defined("DS")) {
    define("DS", DIRECTORY_SEPARATOR);
}

require_once($rootDir . DS . "vendor" . DS . "autoload.php");

use Proxima\core\WebPath;
use Proxima\core\Response;
use Proxima\core\ApiRequest;
use ProximaCustom\services\CasService;

$api = new ApiRequest();
// 상품 조회
$api->get(function ($params, $request) {
    $service = new CasService();
    $type = $request->type;
    $keyword = trim($request->keyword);
    if (empty($keyword)) {
        Response::echoJsonOk([]);
        die();
    }

    $itemCd = null;
    $itemNm = null;
    if ($type === 'item_code') {
        $itemCd = $keyword;
    } elseif ($type === 'item_name') {
        $itemNm = $keyword;
    } else {
        throw new \Exception('검색 조건은 상품명 또는 상품코드여야 합니다.');
    }

    $pageNumber = $request->start ?? 1;
    $pageSize = $request->limit ?? 10;
    $needTotalCount = true;

    $result = $service->getItem($request->item_channel_code, $itemCd, $itemNm, $pageNumber, $pageSize, $needTotalCount);
    $items = $result['result']['itemInfoList'] ?? null;

    $data = [];
    foreach ($items as $item) {
        // 영구중단인 상품 제외
        if (!empty($item) && $item['slCls'] !== 'D') {
            // 영구중단인 상품 제외
            $item['slClsNm'] = $service->getItemStatusName($item['slCls']);
            if (!empty($item['imageUrl'])) {
                // 이미지 url 이 //qa-itemimage.cjmall.net/goods_images/55/278/55199278L.jpg 형식이라 앞이 프로토콜을 붙여야 함
                $item['imageUrl'] = WebPath::getDefaultProtocol() . $item['imageUrl'];
            }
            $data[] = $item;
        }
    }

    $totalCount = 0;
    $pagingInfo = $result['result']['paginationInfoTuple'] ?? null;
    if ($pagingInfo) {
        $totalCount = $pagingInfo['totalCount'] ?? 0;
    }

    $response = [
        'success' => true,
        'totalCount' => $totalCount,
        'data' => $data
    ];
    Response::echoJson($response);
});

$api->run();
