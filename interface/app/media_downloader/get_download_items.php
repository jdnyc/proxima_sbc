<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php'); 
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php'); 

use \Proxima\core\Request;
use \Proxima\core\Response;
use \Proxima\models\content\Media;
use \Proxima\models\content\Content;
use \ProximaCustom\interfaces\app\media_download\DownloadManager;

$mediaIds = explode(',', Request::get('media_ids'));

$medias = Media::findMedias($mediaIds);
$contentIds = [];
foreach($medias as $media) {
    $contentIds[] = $media->get('content_id');
}

$contents = Content::findContents($contentIds);

$isCustom = false;
if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\interfaces\app\media_download\DownloadManager')) {
    $isCustom = true;    
}

$files = [];
foreach($medias as $media) {

    if(!$media->isEmpty()) {    
        
        $orgFileName;

        $content = $contents[$media->get('content_id')];
        if($isCustom) {
            $orgFileName = DownloadManager::makeOrgFileName($media, $content);
        } else {
            $orgFileName = makeOrgFileName($media, $content);
        }
        
        $files[] = [
            'media_id' => $media->get('media_id'),
            'file_path' => $media->get('path'),
            'org_file_name' => $orgFileName
        ];
    }
}

Response::echoJson($files);
die();

function makeOrgFileName($media, $content)
{
    // 2018.03.05 hkkim 이미지는 원본파일명으로 다운로드 시켜준다.
    if($content->get('bs_content_id') == IMAGE) {
        $orgFileName = $content->systemMetadata()->get('sys_ori_filename');
        if(empty($orgFileName)) {
            $fileName = $content->get('title');
        } else {
            $fileName = pathinfo($orgFileName, PATHINFO_FILENAME);
        }        
    } else {
        // 해당 미디어의 원본파일이름 대신 콘텐츠 제목을 사용한다.    
        $fileName = $content->get('title');
    }    

    return stripInvalidFileName($fileName);
}