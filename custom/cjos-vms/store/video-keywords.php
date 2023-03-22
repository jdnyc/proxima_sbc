<?php
/**
 * 동영상 키워드 API
 */
$rootDir = dirname(dirname(dirname(__DIR__)));
if (!defined("DS")) {
    define("DS", DIRECTORY_SEPARATOR);
}
    
require_once($rootDir . DS . "vendor" . DS ."autoload.php");

use Proxima\core\Response;
use Proxima\core\ApiRequest;
use ProximaCustom\models\VideoKeyword;

$api = new ApiRequest();
// 동영상 키워드 조회
$api->get(function ($params) {
    $keyword = $params['keyword'] ?? '';
    if (empty($keyword)) {
        Response::echoJsonOk([]);
        die();
    }
    $keywords = VideoKeyword::searchKeyword($keyword);

    $data = [];
    foreach ($keywords as $keyword) {
        $data[] = [
            'keyword' => $keyword->get('keyword')
        ];
    }
    Response::echoJsonOk($data);
});

// 동영상 키워드 저장
$api->post(function ($input) {
    $keyword = $input['keyword'] ?? '';
    if(empty($keyword)) {
        Response::echoJsonOk('keyword is empty.');
        die();
    }
    // 중복 체크
    $foundKeyword = VideoKeyword::findByKeyword($keyword);
    if(!is_null($foundKeyword)) {
        Response::echoJsonOk('keyword is already exists.');
        die();
    }

    $keyword = str_replace(' ', '', $keyword);
    
    VideoKeyword::create($keyword);
    Response::echoJsonOk();
});

$api->run();
