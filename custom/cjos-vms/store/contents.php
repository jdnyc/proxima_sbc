<?php

use Proxima\core\Session;

$rootDir = dirname(dirname(dirname(__DIR__)));
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require_once($rootDir . DS . 'vendor' . DS .'autoload.php');

require_once(dirname(dirname(dirname(__DIR__))) . DS . 'store/get_content_list/libs/'. DS .'functions.php');    


Session::init();

use Proxima\core\Response;
use Proxima\core\ApiRequest;
use ProximaCustom\services\ContentService;

$api = new ApiRequest();
// 채널 조회
$api->get(function ($params) {
    /*
    @ud_content_id=
    @filter_type=category
    @filter_value=카테고리 패스
    @limit=50
    @sort=category_title or title ...
    @dir=DESC/ASC
    @aspect_ratio: ""
    @channel: ""
    @from_date: "2019-01-21"
    @item_code: ""
    @modify_code: ""
    @pgm_code: ""
    @title: ""
    @to_date: "2019-02-20"
    @use: ""
    @video_code: ""
    */

    $udContentId = $params['ud_content_id'];
    $filterType = $params['filter_type'];
    $categoryPath = $params['filter_value'];

    if (empty($udContentId)) {
        throw new \Exception('ud_content_id is required.');
    }

    // conditions
    $conditionFields = ['from_date', 'to_date', 'updater_id', 'title', 'aspect_ratio', 'channel_code', 'item_code', 'pgm_code', 'use', 'video_code'];
    $conditions = [];
    foreach ($conditionFields as $conditionField) {
        $param = $params[$conditionField] ?? null;
        if (empty($param)) {
            continue;
        }
        $conditions[$conditionField] = $param;
    }

    $pagination = [
        'offset' => $params['start'] ?? 0,
        'limit' => $params['limit'] ?? 50
    ];
    $sort = [
        'field' => $params['sort'],
        'dir' => $params['dir']
    ];

    $contentService = new ContentService();
    $contents = $contentService->search($udContentId, $filterType, $categoryPath, $conditions, $pagination, $sort);

    $results = fetchMetadata($contents['rows']);

    $total = $contents['totals'][$udContentId];
    $response = [
        'success' => true,
        'total' => $total,
        'results' => $results,
        'ud_total_list' => $contents['totals'],
    ];

    Response::echoJson($response);
});

$api->run();
