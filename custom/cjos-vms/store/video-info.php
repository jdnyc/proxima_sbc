<?php
/**
 * Video url 조회
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

$api->get(function ($params, $request) {
    $videoCode = $request->video_code;
    if(empty($videoCode)) {
        throw new \Exception('video_code required');
    }

    $service = new CasService();
    $data = $service->getVideoInfo($videoCode);
    $data = $data['result'];
    Response::echoJsonOk($data);
});

$api->run();
