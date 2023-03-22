<?php

namespace Api\Models;

use Api\Models\DataLog;
use Illuminate\Database\Eloquent\Model;

/**
 * 로깅이 가능한 모델
 */
abstract class LogModel extends Model
{
    // 로깅 여부
    public $logging = false;

    /**
     * 이벤트시 로깅 처리
     */
    protected static function boot()
    {
        //retrieved, creating, created, updating, updated, saving, saved, deleting,  deleted, restoring, restored
        static::creating(function ($model) {
            return $model->logging('create', $model);
        });
        static::deleting(function ($model) {
            return $model->logging('deleting', $model);
        });
        static::deleted(function ($model) {
            return $model->logging('deleted', $model);
        });
        static::updating(function ($model) {
            return $model->logging('update', $model);
        });
        parent::boot();
    }

    /**
     * 변경전 변경 후 로그 기록 함수
     *
     * @param string $funcName
     * @param Model $model
     * @return void
     */
    public function logging($funcName, $model)
    {
        //모델에 로깅여부 정의
        if (!$model->logging) {
            return true;
        }

        $regist_ip = get_server_param('REMOTE_ADDR', '127.0.0.1');

        $regist_user_id = "unknown";
        if (auth()) {
            $regist_user_id = auth()->user()->user_id ?? null;
            if($regist_user_id === null) {
                api_abort('`user_id` is required.');
            }
        } else if (isset($model->regist_user_id) && $model->regist_user_id) {
            $regist_user_id = $model->regist_user_id;
        }

        $targetId = $model->id;

        $current = json_encode($model->original);
        $change = json_encode($model->attributes);

        $log = new DataLog();
        $log->before_value = $current;
        $log->after_value = $change;
        $log->action = $funcName;
        $log->channel = $model->table;
        $log->regist_user_id = $regist_user_id;
        $log->regist_ip = $regist_ip;
        $log->target_id = $targetId;
        $log->save();

        return true;
    }
}
