<?php

namespace Api\Services;

use Api\Models\Task;
use Api\Types\TaskStatus;
use Api\Services\BaseService;

class TaskService extends BaseService
{
    public function find($taskId)
    {
        $task = Task::find($taskId);
        return $task;
    }

    public function findOrFail($taskId)
    {
        $task = self::find($taskId);
        if ($task === null) {
            api_abort_404('Task');
        }
        return $task;
    }

    public function getProcessingCount($module, $taskType)
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

    public function getByRootId($taskId)
    {
        $tasks = Task::where('root_task', $taskId)
            ->orderBy('task_id', 'asc')
            ->with('targetFile')
            ->get();
        return $tasks;
    }
    public function taskListByContentId($contentId){
        $tasks = Task::where('src_content_id', $contentId)
            ->orderBy('task_id', 'asc')
            ->with('targetFile')
            ->get();
        return $tasks;
    }
    public function getByRootIdByContentId($contentId, $workflowChannel)
    {
        $tasks = Task::where('destination', $workflowChannel)
            ->where('src_content_id', $contentId)
            ->orderBy('task_id', 'asc')
            ->with('targetFile')
            ->get();
        return $tasks;
    }

    public function getByTrgFileId($fileId)
    {
        $task = Task::where('trg_file_id', $fileId)
            ->whereIn('type', [60, 80])
            ->select('task_id', 'target', 'status', 'creation_datetime', 'complete_datetime', 'start_datetime', 'update_datetime', 'trg_file_id', 'src_file_id')
            ->orderBy('task_id', 'desc')->first();
        return $task;
    }

    /**
     * 레거시 작업매니저 조회
     *
     * @return TaskManager
     */
    public function getTaskManager()
    {
        // 미디어 변환 워크플로우 등록
        require_once(dirname(dirname(__DIR__)) . '/workflow/lib/task_manager.php');
        global $db;
        return new \TaskManager($db);
    }
}
