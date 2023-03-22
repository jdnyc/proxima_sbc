<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/functions.php';

use Proxima\core\Request;
use Proxima\core\Session;
use Proxima\core\Response;
use Proxima\models\content\Media;
use Proxima\models\content\Content;

Session::init();

try {
    $user = Session::get('user');
    if(empty($user['user_id'])) {
        throw new \Exception('세션 정보가 없습니다. 로그인 후 다시 시도해 주세요.');        
    }

    // array
    $contentIds = json_decode(Request::post('contentIds'), false);

    $contents = Content::findContents($contentIds);
    foreach($contents as $content) {
        $orgMedias = $content->medias([Media::MEDIA_TYPE_ORIGINAL]);

        $mediaStatus = trim($orgMedias[0]->get('status'));
        
        if(empty($mediaStatus) && $content->get('is_deleted') == 'N') {
            if(count($contents) == 1) {
                throw new \Exception('이미 복구된 콘텐츠 입니다.');
            }
            continue;
        } else if($mediaStatus == 'M' || 
            $mediaStatus == 'B') {
            throw new \Exception($content->get('title') . '은 원본파일이 이미 삭제되었습니다. <br/>해당콘텐츠를 제외하고 다시 시도 하세요.');        
        }
        $content->restore();

        // 로그 추가
        insertLog('restore_del_content', $user['user_id'], '', '삭제된 콘텐츠 복구');
    }

    Response::echoJsonOk();
} catch(\Exception $ex) {
    Response::echoJsonError($ex->getMessage());
}