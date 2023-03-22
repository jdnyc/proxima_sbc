<?php

namespace Api\Models;

use Api\Models\BaseModel;

class Request extends BaseModel
{
    protected $table = 'tb_ord';

    protected $primaryKey = 'ord_id';
    protected $keyType = 'string';
    public $incrementing = false;

    const CREATED_AT = 'input_dtm';
    const UPDATED_AT = 'updt_dtm';

    public $sort = 'input_dtm';

    protected $dates = [
        'completed_dtm'
    ];
    
    protected $casts = [
        'ord_id' => 'string'  
    ];


    // 의뢰자 유저 정보
    public function inputr()
    {
        return $this->belongsTo(\Api\Models\User::class, 'inputr_id', 'user_id');
    }
    // 의뢰자 유저 정보
    public function workUser()
    {
        return $this->belongsTo(\Api\Models\User::class, 'ord_work_id', 'user_id');
    }
    // 유저 메타
    public function usrMeta()
    {
        return $this->hasOne(\Api\Models\ContentUsrMeta::class ,'usr_content_id' ,'content_id' );
    }
}
