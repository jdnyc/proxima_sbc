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
use ProximaCustom\services\CasService;
use ProximaCustom\services\ContentService;

$api = new ApiRequest();

$api->get(function ($params, $request) {
    $type = $request->type;
    $keyword = $request->keyword ?? '';

    $data = [];
    switch ($type) {
        case 'video_code': {
            $data = searchByVideoCode($keyword);
            break;
        }
        case 'video_name': {
            $data = searchByVideoName($keyword);
            break;
        }
        case 'item_code': {
            $data = searchByItemCode($keyword);
            break;
        }
        default:
            throw new \Exception('Invalid search type.');
    }

    Response::echoJsonOk($data);
});

$api->run();


function searchByVideoCode($keyword)
{
    return [
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
    ];
}

function searchByVideoName($keyword)
{
    return [
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
    ];
}

function searchByItemCode($keyword)
{
    return [
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
    ];
}
