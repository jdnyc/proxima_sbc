<?php

namespace Api\Models;

use Api\Models\DataLog;
use Api\Models\LogModel;

/**
 * 기본 모델 클래스 class
 */
abstract class BaseModel extends LogModel
{
    /**
     * Eloquent 기본 생성일과 수정일 필드
     */
    const CREATED_AT = 'regist_dt';
    const UPDATED_AT = 'updt_dt';

    /**
     * Date 포맷
     *
     * @var string
     */
    protected $dateFormat = 'YmdHis';
    /**
     * PK
     *
     * @var string
     */
    protected $primaryKey = 'id';
    //protected $perPage = 20;

    /**
     * DB 저장 시 datetime 잘못 처리 되는 문제 수정
     *
     * @param mixed $value
     * @return \Illuminate\Support\Carbon
     */
    protected function asDateTime($value)
    {
        if ($value && is_numeric($value)) {
            return \Carbon\Carbon::createFromFormat($this->dateFormat, $value);
        }
        return parent::asDateTime($value);
    }

    /**
     * 모델을 json등 으로 serialize될 때 포맷 설정
     * 여기서 설정하지 않으면 $dateFormat의 설정을 따라간다.
     *
     * @param \DateTimeInterface $date
     * @return void
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format(\DateTimeInterface::ISO8601);
    }

    // Customized
    /**
     * 기본 정렬 필드
     *
     * @var string
     */
    public $sort = 'id';
    /**
     * 기본 정렬 순서
     *
     * @var string
     */
    public $dir = 'desc';

    /**
     * 정렬 가능 필드
     *
     * @var array
     */
    public $sortable = ['id'];

    /**
     * 관계 추가
     *
     * @return void
     */
    public function addRelationships()
    {
        $relations = request()->includes;
        if (empty($relations)) {
            return;
        }

        foreach ($relations as $relation) {
            $attribute = $this->getAttribute(camel_case($relation));
            if ($attribute) {
                $this->setAttribute(snake_case($relation), $attribute);
            }
        }
    }

    /**
     * 변경전 변경 후 로그 기록 함수
     *
     * @param [type] $funcName
     * @param [type] $model
     * @return void
     */
    public function logging($funcName, $model)
    {
        //모델에 로깅여부 정의
        if (!$model->logging) {
            return true;
        }

        $regist_ip = get_server_param('REMOTE_ADDR', '127.0.0.1');

        if (auth()) {
            $regist_user_id = auth()->user()->user_id ?? null;
            if($regist_user_id === null) {
                api_abort('`user_id` is required.');
            }
        } else if ($model->regist_user_id) {
            $regist_user_id = $model->regist_user_id;
        } else {
            $regist_user_id = "unknown";
        }

        $current = json_encode($model->original);
        $change = json_encode($model->attributes);

        $log = new DataLog();
        $log->before_value = $current;
        $log->after_value = $change;
        $log->action = $funcName;
        $log->channel = $model->table;
        $log->regist_user_id = $regist_user_id;
        $log->regist_ip = $regist_ip;
        $log->save();

        return true;
    }


    /**
     * date field format
     */
    public function date_str($date, $format = 'Y-m-d')
    {
        $carbon = new \Carbon\Carbon($date);
        return $carbon->format($format);
    }
}
