<?php

namespace Api\Models;

use Api\Models\BaseModel;

/**
 * 작업 모듈
 * 
 * @property int $task_type_id 아이디(pk)
 * @property string $type 작업유형
 * @property string $name 작업유형 명
 * @property int $show_order 정렬순서
 */
class TaskType extends BaseModel
{
    protected $table = 'bc_task_type';

    protected $primaryKey = 'task_type_id';

    protected $guarded = [];

    public function taskRules()
    {
        return $this->hasMany(\Api\Models\TaskRule::class, 'task_type_id', 'task_type_id');
    }
}
