<?php
/**
 * 동영상 검색 API
 */
$rootDir = dirname(dirname(dirname(__DIR__)));
if (!defined("DS")) {
    define("DS", DIRECTORY_SEPARATOR);
}

require_once($rootDir . DS . "vendor" . DS ."autoload.php");

use Proxima\core\Response;
use Proxima\core\ApiRequest;
use ProximaCustom\models\Item;
use ProximaCustom\services\CasService;
use ProximaCustom\services\ContentService;

$api = new ApiRequest();

$api->get(function ($params) {
    $type = $params['type'];
    $keyword = $params['keyword'];
    // 공백문자 처리
    if (empty(trim($keyword))) {
        Response::echoJsonOk([]);
        die();
    }
    
    $contentService = new ContentService();

    $contents = null;
    $data = [];
    switch ($type) {
        case 'video_code': {
            $contents = $contentService->searchByVideoCode($keyword);
            $data = makeDataByVideo($contents);
            break;
        }
        case 'video_name': {
            $contents = $contentService->searchByVideoName($keyword);
            $data = makeDataByVideo($contents);
            break;
        }
        case 'item_code': {
            $items = Item::findByCode($keyword);
            if (count($items) > 0) {
                $contents = $contentService->searchByItems($items);
                $data = makeDataByItem($items[0], $contents);
            }
            break;
        }
        default:
            throw new \Exception('Invalid search type.');
    }

    Response::echoJsonOk($data);
});

$api->run();

function makeDataByItem($item, $contents)
{
    // 상품별로 비디오 정보 등 추가
    $data = [];
    $no = 1;

    $videos = [];
    foreach ($contents as $content) {

        /*
         [
            'no' => 1,
            'item_code' => '50339358',
            'item_name' => '[스킨푸드] 피치뽀송 멀티 피니시 파우더소용량',
            'item_status' => '상품상태',
            'videos' => [
                [
                    'video_code' => '0424236',
                    'video_name' => '모찌피치 3회',
                    'aspect_ratio' => '16:9',
                    'use' => 'Y'
                ],[
                    'video_code' => '0424288',
                    'video_name' => '모찌피치 4회',
                    'aspect_ratio' => '1:1',
                    'use' => 'N'
                ]
            ]
        ]
        */
        $videos[] = [
            'video_code' => $content->get('usr_video_code'),
            'video_name' => $content->get('title'),
            'aspect_ratio' => $content->get('usr_aspect_ratio'),
            'use' => $content->get('usr_use')
        ];
    }
        
    $service = new CasService();
    $data[] = [
            'no' => $no++,
            'item_code' => $item->get('code'),
            'item_name' => $item->get('name'),
            'item_status' => $service->getItemStatusName($item->get('sl_cls')),
            'videos' => $videos
        ];
    return $data;
}


function makeDataByVideo($contents)
{
    // 콘텐츠별로 상품 정보 등 추가
    $data = [];
    $no = 1;
    foreach ($contents as $content) {

        /*동영상 코드
        [
            'no' => 1,
            'video_code' => '0424236',
            'video_name' => '모찌피치 3회',
            'aspect_ratio' => '16:9',
            'use' => 'Y',
            'items' => [
                [
                    'item_code' => '5033935',
                    'item_name' => '[스킨푸드] 피치뽀송 멀티 피니시 파우더소용량',
                    'item_status' => '정상',
                    'display_order' => 1,
                    'display' => 'Y'
                ],[
                    'item_code' => '50432095',
                    'item_name' => '[시연] 피치뽀송 멀티 피니시 파우더소용량',
                    'item_status' => '매진',
                    'display_order' => 2,
                    'display' => 'Y'
                ]
            ]
        ]
        */
        $itemList = json_decode($content->get('usr_item_list'));
        // [{"repItemYn":"Y","itemCd":"38162767","itemNm":"ch244 걱정은말고행복해지세요 현관문시트지","chnCd":"30001001","slCls":"A","slClsNm":"정상","dispOrder":0,"dispYn":"Y"}]
        $items = [];
        if (!empty($itemList)) {
            foreach ($itemList as $item) {
                $items[] = [
                    'item_code' => $item->itemCd,
                    'item_name' => $item->itemNm,
                    'item_status'=> $item->slClsNm,
                    'display_order' => $item->dispOrder,
                    'display' => $item->dispYn === 'Y' ? '전시' : '미전시'
                ];
            }
        }
        
        $data[] = [
            'no' => $no++,
            'video_code' => $content->get('usr_video_code'),
            'video_name' => $content->get('title'),
            'aspect_ratio' => $content->get('usr_aspect_ratio'),
            'use' => $content->get('usr_use'),
            'items' => $items
        ];
    }
    return $data;
}
