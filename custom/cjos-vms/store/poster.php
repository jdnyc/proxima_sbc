<?php
/**
 * 포스터 변경
 */
$rootDir = dirname(dirname(dirname(__DIR__)));
if (!defined("DS")) {
    define("DS", DIRECTORY_SEPARATOR);
}

require_once($rootDir . DS . "vendor" . DS . "autoload.php");

use Proxima\core\Path;
use Proxima\core\WebPath;
use Proxima\core\Response;
use Proxima\core\ApiRequest;
use ProximaCustom\core\CdnUrl;
use Proxima\models\content\Media;
use Proxima\models\system\Module;
use Proxima\models\system\Storage;
use Proxima\models\content\Content;
use Proxima\models\content\Thumbnail;
use ProximaCustom\services\CasService;
use ProximaCustom\services\CfsUploadService;

$api = new ApiRequest();

// 포스터 조회
$api->get(function ($input, $request) {
    $contentId = $request->content_id;
    if (empty($contentId)) {
        throw new \Exception('content_id is required.');
    }
    $medias = Media::findByContentIds([$contentId], [Media::MEDIA_TYPE_THUMB]);
    if (empty($medias)) {
        Response::echoJsonOk([
            'poster_url' => null
        ]);
        die();
    }
    $posterUrl = $medias[0]->get('url');
    if (empty($posterUrl)) {
        $posterUrl = WebPath::makeUrl(WebPath::makeProxyPath($medias[0]->get('path')), true);
    } else {
        $cdnUrl = CdnUrl::getUrl();
        $posterUrl = $cdnUrl . $posterUrl;
    }

    $data = [
        'poster_url' => $posterUrl
    ];
    Response::echoJsonOk($data);
});
// 포스터 변경
$api->post(function ($input, $request) {
    //Check if the file is well uploaded
    if ($_FILES['file']['error'] > 0) {
        throw new \Exception('Error during uploading, try again(error : ' . $_FILES['file']['error'] . ')');
    }

    //We won't use $_FILES['file']['type'] to check the file extension for security purpose

    //Set up valid image extensions
    $extsAllowed = array('jpg', 'jpeg', 'png', 'gif');

    //Extract extention from uploaded file
    //substr return ".jpg"
    //Strrchr return "jpg"

    $extUpload = strtolower(substr(strrchr($_FILES['file']['name'], '.'), 1));

    //Check if the uploaded file extension is allowed

    $data = [];
    if (in_array($extUpload, $extsAllowed)) {
        $uploadedFilePath = $_FILES['file']['tmp_name'];
        $thumb = Thumbnail::findByContentId($request->content_id);
        $newThumbInfo = $thumb->replace($uploadedFilePath);

        // CFS 스토리지 업로드
        $cfsUploadService = new CfsUploadService();
        $modules = Module::findByTaskRuleId(200);
        if (count($modules) <= 0) {
            throw new \Exception('Can not upload. Available module does not exists.');
        }
        $content = Content::find($request->content_id);
        $metadata = [
            'contentId' => $content->get('content_id'),
            'type' => 'media',
            'mediaId' => $thumb->get('media_id'),
            'sceneId' => null
        ];
        $thumbFilePath = makeThumbFilePath($thumb);
        $result = $cfsUploadService->uploadSync(getServiceEndPoint($modules[0]), $content, $thumbFilePath, $metadata);

        if ($result['ok']) {
            $file = $result['file'];
            $thumb->set('url', $file['url']);
            $thumb->save();

            $url = CdnUrl::getUrl() . $file['url'];
            $data = [
                'media_id' => $thumb->get('media_id'),
                'path' => $thumb->get('path'),
                'url' => $url
            ];
        } else {
            throw new \Exception($result['error']);
            // $data = [
            //     'media_id' => $thumb->get('media_id'),
            //     'path' => $thumb->get('path'),
            //     'url' => $thumb->get('url')
            // ];
        }
    } else {
        throw new \Exception('File is not valid. Please try again');
    }
    Response::echoJsonOk($data);
});

$api->run();

function getServiceEndPoint($module)
{
    $mainIp = $module->get('main_ip');
    return 'http://' . $mainIp;
}

function makeThumbFilePath($thumb)
{
    $subPath = $thumb->get('path');
    $lowresStorage = Storage::find(3);
    $thumbFilePath = Path::join($lowresStorage->getPath('win'), $subPath);
    $thumbFilePath = str_replace('/', '\\', $thumbFilePath);
    return $thumbFilePath;
}
