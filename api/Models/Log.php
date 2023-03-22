<?php

namespace Api\Models;

use Api\Models\BaseModel;

/**
 * 로그 테이블
 * 
 * @property int $log_id 아이디(pk)
 * @property string $ACTION 활성화 여부(1:활성화, 0:비활성화)
 * @property string $USER_ID 메인 아이피 주소
 */
class Log extends BaseModel
{
    protected $table = 'bc_log';

    protected $primaryKey = 'log_id';

    
    const CREATED_AT = null;//'regist_dt';
    const UPDATED_AT = null;//'updt_dt';
    const DELETED_AT = null;//'delete_dt';';
    

    protected $guarded = [];

    public $sort = 'log_id';
    public $dir = 'desc';
}
