<?php
// 미디어 다운로드 이력만 남긴다
$rootDir = dirname(dirname(dirname(__DIR__)));
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
    
require_once($rootDir . DS . 'vendor' . DS .'autoload.php');

use Proxima\core\Session;
use Proxima\core\Response;
use Proxima\core\ApiRequest;
use Proxima\models\system\Log;
use ProximaCustom\services\CasService;

Session::init();

$api = new ApiRequest();

$api->post(function ($input, $request) {
    $user = Session::get('user');
    $userId = $user['user_id'];
    $mediaId = $request->media_id;
    $mediaType = $request->media_type;

    if (empty($userId)) {
        throw new \Exception('user id is empty.');
    }
    if (empty($mediaId)) {
        throw new \Exception('media id required.');
    }
    if (empty($mediaType)) {
        throw new \Exception('media type required.');
    }
    
    $media = \Proxima\models\content\Media::find($mediaId);
    $content = $media->content();
    Log::create('download', $userId, $content, $mediaType);
    
    Response::echoJsonOk();
});

$api->run();
