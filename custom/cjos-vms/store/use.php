<?php
/**
 * 업로드 상태 등록 API
 */
$rootDir = dirname(dirname(dirname(__DIR__)));
if (!defined("DS")) {
    define("DS", DIRECTORY_SEPARATOR);
}
    
require_once($rootDir . DS . "vendor" . DS ."autoload.php");

use Proxima\core\Response;
use Proxima\core\ApiRequest;
use Proxima\models\system\Task;
use Proxima\models\content\Media;
use Proxima\models\content\Scene;
use Proxima\models\system\TaskLog;
use Proxima\models\content\Content;
use ProximaCustom\services\CasService;

$api = new ApiRequest();

// 사용 메타데이터 업데이트
$api->post(function ($input, $request) {
    $contentId = $request->content_id;
    if (empty($contentId)) {
        throw new \Exception('content_id is required.');
    }
    $userId = $request->user_id;
    if (empty($userId)) {
        throw new \Exception('user_id is required.');
    }
    $use = strtoupper($request->use); // Y or N
    if (empty($contentId)) {
        throw new \Exception('use value is required.');
    }

    if ($use !== 'Y' && $use !== 'N') {
        throw new \Exception('Invalid value. use : ' . $use);
    }

    $content = Content::find($contentId);
    if (!$content) {
        throw new \Exception('Content not found.');
    }
    $userMeta = $content->userMetadata();
    $userMeta->set('usr_use', $use === 'Y' ? '사용' : '미사용');
    $userMeta->save();

    insertLog('edit', $userId, $contentId, '메타수정됨(사용여부) : ' . $use);
    
    $service = new CasService();
    $result = $service->syncContent($content);

    if ($result['status'] == 200 && $result['message'] === 'OK') {
        Response::echoJsonOk();
    } else {
        Response::echoJsonError('CAS에 반영하지 못했습니다.' . $result['message'] ?? '');
    }
});

$api->run();
