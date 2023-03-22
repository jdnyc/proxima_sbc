<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;

/**
 * 데이터 로그
 * 
 * @property int $id 아이디(pk)
 * @property string $channel 로그 구분
 * @property string $action 행위
 * @property string $before_value 변경전
 * @property string $after_value 변경후
 * @property string $regist_ip 등록IP
 * @property string $dc 설명
 */
class DataLog extends BaseModel
{
    const UPDATED_AT = null;

    protected $table = 'data_log';
    protected $primaryKey = 'id';

    public $logging = false;

    protected $fillable = [
    ];
}