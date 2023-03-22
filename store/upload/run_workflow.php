<?php
/**
 * 2018.07.03 hkkim
 * 1. 콘텐츠 등록
 * 2. 파일 업로드
 * 3. 이 페이지를 호출하면 해당 콘텐츠에 대해 인제스트 워크플로우를 수행한다.
*/

$rootDir = dirname(dirname(__DIR__));

require_once($rootDir . '/lib/config.php');
require_once($rootDir . '/workflow/lib/task_manager.php');

use Proxima\core\Session;
use Proxima\core\Response;
use Proxima\core\ApiRequest;
use Proxima\models\content\Content;
use ProximaCustom\models\VideoServer;
use Proxima\models\system\UserContentStorage;

Session::init();

$api = new \Proxima\core\ApiRequest();

$api->post(function($params) {

    global $db;
    try {
        $contentId = $params['content_id'];
        $userId = Session::get('user')['user_id'];
        $channel = $params['channel'];
        $filename = $params['filename'];

        $ext = pathinfo($filename, PATHINFO_EXTENSION );

        $content = Content::find($contentId);
        if(empty($content) || empty($content->get('content_id'))) {
            throw new \Exception('Could not find the content.');
        }

        $storage = UserContentStorage::find($content->get('ud_content_id'), 'upload')->storage();
        $path = $storage->getPath();
        
        //$fileFullName = $path . '/' . $content->get('content_id') . '.' . $ext;

        // 워크플로우 실행

        $taskManager = new \TaskManager($db);
        $taskManager->insert_task_query_outside_data($contentId, $channel, 1, $userId, $filename);
    
        Response::echoJsonOk();
    } catch (\Exception $e) {
        Response::echoJsonError($e->getMessage());
    }
    
});


$api->run();
