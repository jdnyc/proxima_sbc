<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

use Proxima\core\Session;
use \Proxima\core\Request;
use \Proxima\core\Response;
use \Proxima\models\content\Media;
use \Proxima\models\content\Content;

Session::init();

$downloadType = Request::post('download_type'); // content, pfr, media
$isWindowsOs = Request::post('is_win_os') == 'true' ? true : false;

if($downloadType == 'content') {
    $contentIdsStr = Request::post('content_ids');
    $reason = Request::post('reason');
    $mediaType = Request::post('media_type');
    $requestType = Request::post('req_type');
    
    $contentIds = explode(',', $contentIdsStr);
    
    $medias = Media::findByContentIds($contentIds, [$mediaType]);
    
} else if($downloadType == 'pfr') {
    $mediaIdsStr = Request::post('media_ids');

    $mediaIds = explode(',', $mediaIdsStr);    

    $medias = Media::findMedias($mediaIds);
} else {
    //Response::echoJsonError('다운로드 목록 요청 유형이 잘못되었습니다.(content, pfr)');
    //MSG02201 잘못된 접근입니다. 다시 시도 해 주시기 바랍니다.
    Response::echoJsonError(_text('MSG02201').'(content, pfr)');
    die();
}

$mediaIds = [];
$deletedContentIds = [];
$httpPaths = [];
$message = '';  
$userId = Session::get('user')['user_id'];

foreach($medias as $media) {
    if(!empty($media->get('deleted_date'))) {
        $deletedContentIds[] = $media->get('content_id');
    } else {
        $mediaIds[] = $media->get('media_id');
        writeDownloadLog($mediaType, $userId, $media->get('content_id'), $reason);

        if($requestType != 'G') {
            /*
             * 상세보기 내 그룹콘텐츠 목록에서 선택시 첫번째 콘텐츠는 isGroup이 G라서 전체가 다운로드 되기때문에
             * requestType으로 구분을 두어 목록에서 요청된것인지 상세보기 그룹목록에서 요청된 부분인지 확인후 처리하기 위해서 추가
             * 2018.01.26 Alex
            */
            // 그룹 콘텐츠 일 경우 하위 콘텐츠 미디어 추가
            $subContentMedias = getSubContentsMedias($media);
            foreach($subContentMedias as $subContentMedia) {
                $mediaIds[] = $subContentMedia->get('media_id');
                writeDownloadLog($mediaType, $userId, $subContentMedia->get('content_id'), $reason);
            }
        }
        
        if(!$isWindowsOs) {
            $title = str_replace(';', '', $media->content()->get('title'));
            $httpPaths[] = getHttpPath($media, $title);
        }
    }
}

// 삭제된 콘텐츠 메세지
if(!empty($deletedContentIds)){
    $deletedMessage = buildDeletedContentMessage($deletedContentIds);
}

if($isWindowsOs) {
    $data = implode(',', $mediaIds);
} else {
    $data = $httpPaths;
}

$response = ['success' => true, 'data' => $data, 'message' => $deletedMessage];
Response::echoJson($response);     

function writeDownloadLog($mediaType, $userId, $contentId, $description = '') {
    // 로그 남김
    $action = $mediaType . '-download';
    insertLog($action, Session::get('user')['user_id'], $contentId, $description);
}

function getHttpPath($media, $title) {
    $download_path = convertSpecialChar($media->get('path'));
    $download_path = str_replace('\\', '/', $download_path);
    $filename = str_replace(array('\\', '/', ':', '*', '?', '"', '<', '>', '|'), '_', $title); 
    $filename = convertSpecialChar($filename);
    $arr_path = explode('.', $download_path);
    $ext = array_pop($arr_path);
    $filename .= '.'.strtolower($ext);

    return $download_path.'?'.$filename;
}

// 삭제된 콘텐츠 메세지 생성
function buildDeletedContentMessage($deletedContentIds) {

    $contents = Content::findContents($deletedContentIds);
    
    $deletedContentTitles = [];
    foreach($contents as $content) {
        $deletedContentTitles[] = $content->get('title');
    }

    //$message = '"' . implode('", "', $deletedContentTitles) . '" 는 파일이 삭제되었습니다.';
    //MSG02122 삭제되었습니다
    $message = '"' . implode('", "', $deletedContentTitles) . '", '._text('MSG02122');

    return $message;
}

function getSubContentsMedias($media)
{
    $content = $media->content();
    $mediaType = $media->get('media_type');
    if($content->get('is_group') != 'G') {
        return [];
    }

    // 그룹일 때 서브 콘텐츠를 조회하고 해당 콘텐츠의 미디어를 조회한다.
    $subContents = $content->subContents();
    $contentIds = [];
    foreach($subContents as $subContent) {
        $contentIds[] = $subContent->get('content_id');        
    }

    $medias = Media::findByContentIds($contentIds, [$mediaType]);

    return $medias;
}
