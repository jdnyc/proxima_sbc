<?php

use Api\Models\TaskRule;
use Proxima\core\Logger;
use Api\Types\TaskStatus;

$rootDir = dirname(dirname(dirname(__DIR__)));

require_once $rootDir . '/vendor/autoload.php';

$logger = new Logger('jobs_schedule');

$settings = \Api\Support\Helpers\DatabaseHelper::getSettings();

$capsule = \Api\Support\Helpers\DatabaseHelper::getConnection($settings);

const JOB_CODE_SNS_PUBLISH = '84';

// 3초에 한번씩 SNS 업로드 작업 조회 후 작업 요청
$taskRule = TaskRule::find(200);

while (true) {
    // upload job scheduling
    try {
        printLog('start job schedule');

        $modules = $taskRule->modules()->where('active', '1')->get();
        printLog('module count : ' . count($modules));

        $now = new \Carbon\Carbon();
        $yesterday = $now->subDay()->format('Ymd') . '000000';
        $tasks = Task::where('type', JOB_CODE_SNS_PUBLISH)
            ->where('status', 'queue')
            ->orderBy('priority')
            ->get();

        echo "task count : " . count($tasks->count()) . "\n";
        printLog('task count : ' . count($tasks->count()));
        if (empty($tasks)) {
            sleep(3);
            continue;
        }

        foreach ($tasks as $task) {
            // 해당 작업의 처리중인 아이피 별 개수
            foreach ($modules as $module) {
                $count =
                    $count = getProcessingCount($module, JOB_CODE_SNS_PUBLISH);

                $module->processing_count = $count;
            }

            $module = selectModule($modules);
            if (is_null($module)) {
                printLog('selected module is null');
                sleep(3);
                continue;
            }

            $content = Content::find($task->src_content_id);
            printLog('before upload');
            $snsService->publish(getServiceEndPoint($module), $task, $content);
            // task 상태 변경
            $task->update([
                'assign_ip' => $module->main_ip,
                'status' => TaskStatus::PROCESSING
            ]);
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

/**
 * 서비스 엔드포인트
 *
 * @param \Api\Models\Module $module
 * @return string
 */
function getServiceEndPoint($module)
{
    $mainIp = $module->main_ip;
    return 'http://' . $mainIp;
}

/**
 * 전체 모듈에서 작업하기 적합한 모듈 선정
 *
 * @param \Api\Models\Module[] $modules
 * @return \Api\Models\Module
 */
function selectModule($modules)
{
    // 최대 작업은 3개
    $MAX_JOB_COUNT = 3;
    for ($i = 0; $i < $MAX_JOB_COUNT; $i++) {
        foreach ($modules as $module) {
            if ($module->processing_count === $i) {
                return $module;
            }
        }
    }
    return null;
}

/**
 * 특정 모듈이 처리중인 특정작업의 카운트
 *
 * @param \Api\Models\Module $module
 * @param \Api\Types\TaskStatus $taskType
 * @return void
 */
function getProcessingCount($module, $taskType)
{
    $now = new \Carbon\Carbon();
    $yesterday = $now->subDay()->format('Ymd') . '000000';
    $assignIp = $module->main_ip;
    $processingCount = Task::where('type', $taskType)
        ->where('assign_ip', $assignIp)
        ->where('creation_datetime', '>', $yesterday)
        ->where('status', TaskStatus::PROCESSING)
        ->count();
    return $processingCount;
}
