<?php
/**
 * 업로드 상태 등록 API
 */
$rootDir = dirname(dirname(dirname(__DIR__)));
if (!defined("DS")) {
    define("DS", DIRECTORY_SEPARATOR);
}

require_once($rootDir . DS . "vendor" . DS . "autoload.php");

use Proxima\core\Response;
use Proxima\core\ApiRequest;
use Proxima\models\system\Task;
use Proxima\models\content\Media;
use Proxima\models\content\Scene;
use Proxima\models\system\TaskLog;
use Proxima\models\content\Content;
use ProximaCustom\services\CasService;
use ProximaCustom\types\ConvertStatus;

$api = new ApiRequest();

// 업로드 상태 업데이트
$api->post(function ($input) {
    $taskId = $input['taskId'] ?? '';
    $status = $input['status'] ?? '';
    $error = $input['error'] ?? '';

    $result = $input['result'];
    $type = $result['type'] ?? '';
    $mediaId = $result['mediaId'] ?? '';
    $sceneId = $result['sceneId'] ?? '';
    $file = $result['file'] ?? '';

    $fileData = [
        'cfs_path' => $file['path'] ?? '',
        'cfs_filename' => $file['name'] ?? '',
        'url' => $file['url'] ?? ''
    ];

    if ($type === 'media') {
        // type이 media면 미디어에 url 업데이트
        $media = Media::find($mediaId);
        $media->setAttributes($fileData);
        $media->save();
    } elseif ($type === 'scene') {
        // type이 scene이면 scene에 url 업데이트
        $scene = Scene::find($sceneId);
        $scene->setAttributes($fileData);
        $scene->save();
    }

    // task 상태 업데이트
    $task = Task::find($taskId);
    $task->set('status', $status);
    $task->save();

    $contentId = $task->get('src_content_id');

    if ($status === 'complete') {
        // 작업 로그
        TaskLog::addLog($taskId, 'CFS upload done.', $status);
        // content 상태 업데이트
        // 조건 : 콘텐츠의 proxy, proxy_hi, thumbnail의 url이 있고 모든 scene에 url이 있으면 완료 처리        
        $medias = Media::findByContentIds(
            [$contentId],
            [Media::MEDIA_TYPE_PROXY, Media::MEDIA_TYPE_THUMB, 'proxy_hi']
        );
        $isAllUploaded = isAllUploaded($medias);

        if ($isAllUploaded && !empty($sceneId)) {
            $proxyMedia = null;
            foreach ($medias as $media) {
                if ($media->get('media_type') === 'proxy') {
                    $proxyMedia = $media;
                    break;
                }
            }
            $scenes = Scene::getScenesByMediaId($proxyMedia->get('media_id'));
            $isAllUploaded = isAllUploaded($scenes);
        }

        if ($isAllUploaded) {
            $content = Content::find($contentId);
            $content->set('state', ConvertStatus::CONV_COMPLETE);
            $content->save();

            // CAS 동기화 필요
            $service = new CasService();
            $service->syncContent($content);
        }
    } elseif ($status === 'error') {
        // 작업 로그
        TaskLog::addLog($taskId, $error, $status);

        $content = Content::find($contentId);
        if ($content->get('state') != ConvertStatus::TRANS_ERROR) {
            $content->set('state', ConvertStatus::TRANS_ERROR);
            $content->save();

            // CAS 동기화 필요
            $service = new CasService();
            $service->syncContent($content);
        }
    }

    Response::echoJsonOk();
});

$api->run();

function isAllUploaded($models)
{
    $isAllUploaded = false;
    foreach ($models as $model) {
        $url = $model->get('url');
        if (empty($url)) {
            $isAllUploaded = false;
            break;
        } else {
            $isAllUploaded = true;
        }
    }
    return $isAllUploaded;
}
