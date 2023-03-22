<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends BaseModel
{
    // use SoftDeletes;
    protected $table = 'reviews';
    public $sortable = ['id','title','media_id','regist_dt','updt_dt','reject_dt','progrm_nm'];
    protected $fillable = [
        'id','title','media_id','progrm_nm','regist_dt','updt_dt','reject_dt'
    ];
    protected $dates = [
        'compt_dt',
        'reject_dt'
    ];

    public function review_user_nm()
    {
        return $this->belongsTo(\Api\Models\User::class, 'review_user_id', 'user_id');
        
    }

    public function registerer()
    {
        return $this->belongsTo(\Api\Models\User::class, 'regist_user_id', 'user_id');
    }

    /**
     * reviewLog
     *
     * @return \Api\Models\ReviewLog
     */
    public function review_log()
    {
        return $this->hasMany(\Api\Models\ReviewLog::class, 'content_id', 'content_id');
    }


    // 유저 메타
    public function usrMeta()
    {
        return $this->hasOne(\Api\Models\ContentUsrMeta::class ,'usr_content_id' ,'content_id' );
    }

    // 유저 메타
    public function sysMeta()
    {
        return $this->hasOne(\Api\Models\ContentSysMeta::class ,'sys_content_id' ,'content_id' );
    }


}
