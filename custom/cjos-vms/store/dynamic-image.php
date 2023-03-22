<?php
/**
 * 동적 이미지 등록
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
use ProximaCustom\services\CfsUploadService;

$api = new ApiRequest();

// 동적이미지 업로드
$api->post(function ($input, $request) {
    //Check if the file is well uploaded
    if ($_FILES['file']['error'] > 0) {
        throw new \Exception('Error during uploading, try again(error : ' . $_FILES['file']['error'] . ')');
    }

    //We won't use $_FILES['file']['type'] to check the file extension for security purpose

    //Set up valid image extensions
    $extsAllowed = array('gif');

    //Extract extention from uploaded file
    //substr return ".jpg"
    //Strrchr return "jpg"

    $extUpload = strtolower(substr(strrchr($_FILES['file']['name'], '.'), 1));

    //Check if the uploaded file extension is allowed

    $data = [];
    if (in_array($extUpload, $extsAllowed)) {
        $uploadedFilePath = $_FILES['file']['tmp_name'];

        $uploadStorage = Storage::find(2);
        $uploadFileName = date('YmdHis') . '_' . $request->content_id . $_FILES['file']['name'];
        $uploadPath = Path::join($uploadStorage->getPath(), $uploadFileName);

        if (!move_uploaded_file($uploadedFilePath, $uploadPath)) {
            throw new \Exception('Fail to move uploaded file.');
        }

        // CFS 스토리지 업로드
        $cfsUploadService = new CfsUploadService();
        $modules = Module::findByTaskRuleId(200);
        if (count($modules) <= 0) {
            throw new \Exception('Can not upload. Available module does not exists.');
        }
        $content = Content::find($request->content_id);
        $uploadFilePath = makeUploadFilePath($uploadFileName);
        $result = $cfsUploadService->uploadSync(getServiceEndPoint($modules[0]), $content, $uploadFilePath, []);

        @unlink($uploadFilePath);
        if ($result['ok']) {
            $file = $result['file'];

            $url = CdnUrl::getUrl() . $file['url'];
            $data = [
                'url' => $url
            ];
        } else {
            throw new \Exception($result['error']);
        }
    } else {
        throw new \Exception('File is not valid. Please try again.');
    }
    Response::echoJsonOk($data);
});

$api->run();

function getServiceEndPoint($module)
{
    $mainIp = $module->get('main_ip');
    return 'http://' . $mainIp;
}

function makeUploadFilePath($uploadFileName)
{
    $uploadStorage = Storage::find(2);
    $uploadFilePath = Path::join($uploadStorage->getPath('win'), $uploadFileName);
    $uploadFilePath = str_replace('/', '\\', $uploadFilePath);
    return $uploadFilePath;
}
