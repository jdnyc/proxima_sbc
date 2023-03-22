<?php

namespace Api\Models;

use Api\Models\BaseModel;

/**
 * 작업 모듈
 * 
 * @property int $task_rule_id 아이디(pk)
 * @property string $job_name 작업명
 * @property string $parameter 파라메터
 * @property int $source_path 소스경로 스토리지 아이디
 * @property int $target_path 대상경로 스토리지 아이디
 * @property int $task_type_id 작업유형 아이디
 * @property string $source_opt 소스 옵션
 * @property string $target_opt 대상 옵션
 */
class TaskRule extends BaseModel
{
    protected $table = 'bc_task_rule';

    protected $primaryKey = 'task_rule_id';

    protected $guarded = [];

    public function taskType()
    {
        return $this->belongsTo(\Api\Models\TaskType::class, 'task_type_id', 'task_type_id');
    }

    public function sourceStorage()
    {
        return $this->belongsTo(\Api\Models\Storage::class, 'source_path', 'storage_id');
    }

    public function targetStorage()
    {
        return $this->belongsTo(\Api\Models\Storage::class, 'target_path', 'storage_id');
    }

    public function modules()
    {
        return $this->belongsToMany(\Api\Models\Module::class, 'bc_task_available', 'task_rule_id', 'module_info_id', 'task_rule_id', 'module_info_id');
    }
}
