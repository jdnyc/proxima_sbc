<?php

use Proxima\core\Session;
use Proxima\models\content\Media;
use ProximaCustom\core\ViewCustom;
use Proxima\models\content\Content;
use ProximaCustom\services\CasService;
use ProximaCustom\types\ConvertStatus;

Session::init();

$contentId = $_REQUEST['content_id'];
$user = Session::get('user');
$userId = $user['user_id'];

$content = Content::find($contentId);
// VMS 커스터마이징 url을 직접 조회한다.
if ($content->get('state') == ConvertStatus::CONV_COMPLETE && $content->get('status') == CONTENT_STATUS_COMPLETE) {
    $casService = new CasService();
    $userMeta = $content->userMetadata();
    $videoInfo = $casService->getVideoInfo($userMeta->get('usr_video_code'));
    // 고해상도가 보이도록
    $previewPath = $videoInfo['result']['videoUrlHigh'];
} else {
    $previewPath = Media::getPreviewPath($contentId, STREAM_FILE);
}

$sysMeta = $content->systemMetadata();
$FPS = $sysMeta->getFramerate();

//include(dirname(__DIR__) . '/javascript/ext.ux/Custom.MediaDetailWindow.js');
$scriptPath = '/javascript/ext.ux/Custom.MediaDetailWindow.js';
$mediaDetailWindowData = ViewCustom::getScriptData($scriptPath);
// echo $view . "\n";

$scriptPath = '/views/media.js';
$view = ViewCustom::getScriptData($scriptPath);
$args = [
    'userId' => $userId,
    'contentId' => $contentId,
    'previewPath' => $previewPath,
    'FPS' => $FPS,
    'mediaDetailWindowData' => base64_encode($mediaDetailWindowData)
];

$argsJson = json_encode($args);
echo str_replace('{args}', $argsJson, $view);
