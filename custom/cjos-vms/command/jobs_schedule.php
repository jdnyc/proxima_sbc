<?php

use Proxima\core\Logger;
use Proxima\models\system\Task;
use Proxima\models\system\Module;
use Proxima\models\content\Content;
use ProximaCustom\services\CfsUploadService;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

$rootDir = dirname(dirname(dirname(__DIR__)));
    
require_once $rootDir . DS .  'vendor' . DS . 'autoload.php';

$logger = new Logger('jobs_schedule');
$uploadService = new CfsUploadService();

// 3초에 한번씩 CFS 업로드 작업 조회 후 작업 요청
while (true) {
    // upload job scheduling
    try {
        printLog('start job schedule');
        $modules = Module::findByTaskRuleId(200);
        printLog('module count : ' . count($modules));

        $now = new \Carbon\Carbon();
        $yesterday = $now->subDay()->format('Ymd') . '000000';
        $query = "SELECT * FROM bc_task WHERE 
            type='82' AND status = 'queue' ORDER BY priority ASC";
        
        $tasks = Task::queryList($query);
        echo "task count : " . count($tasks) . "\n";
        printLog('task count : ' . count($tasks));
        if (empty($tasks)) {
            sleep(3);
            continue;
        }

        foreach ($tasks as $task) {
            // 해당 작업의 처리중인 아이피 별 개수
            foreach ($modules as $module) {
                $count = Task::getProcessingCount($module, '82');
                
                $module->set('processing_count', (int)$count);
            }

            $module = selectModule($modules);
            if (is_null($module)) {
                printLog('selected module is null');
                sleep(3);
                continue;
            }

            $content = Content::find($task->get('src_content_id'));
            printLog('before upload');
            $uploadService->upload(getServiceEndPoint($module), $task, $content);
            // task 상태 변경
            $task->set('assign_ip', $module->get('main_ip'));
            $task->set('status', 'processing');
            $task->save();
        }
        
        sleep(3);
    } catch (\Exception $e) {
        printLog('error occurred : ' . $e->getMessage());
        $logger->error($e->getMessage());
        sleep(3);
    }
}

function printLog($msg)
{
    echo $msg . "\n";
}

function getServiceEndPoint($module)
{
    $mainIp = $module->get('main_ip');
    return 'http://' . $mainIp;
}

// 전체 모듈에서 작업하기 적합한 모듈 선정
function selectModule($modules)
{
    // 최대 작업은 3개
    $MAX_JOB_COUNT = 3;
    for ($i=0; $i<$MAX_JOB_COUNT; $i++) {
        foreach ($modules as $module) {
            if ($module->get('processing_count') === $i) {
                return $module;
            }
        }
    }
    return null;
}
