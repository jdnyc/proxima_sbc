<?php
/**
 * 사용자목록 API
 */
$rootDir = dirname(dirname(dirname(__DIR__)));
if (!defined("DS")) {
    define("DS", DIRECTORY_SEPARATOR);
}
    
require_once($rootDir . DS . "vendor" . DS ."autoload.php");

use Proxima\core\Response;
use Proxima\core\ApiRequest;

$api = new ApiRequest();
// 사용자 조회
$api->get(function ($params) {
    $mockData = [
        [
            'user_id' => 'admin',
            'user_nm' => '관리자',
            'groups' => '관리자'
        ],
        [
            'user_id' => '005503',
            'user_nm' => '홍길동',
            'groups' => 'PD'
        ],
        [
            'user_id' => '070010',
            'user_nm' => '김수한무',
            'groups' => 'MD'
        ]
    ];

    $data = $mockData;

    Response::echoJsonOk($data);
});

$api->run();
