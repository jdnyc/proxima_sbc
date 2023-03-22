<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReviewLog extends BaseModel
{
    // use SoftDeletes;
    protected $table = 'review_logs';

    const UPDATED_AT = null;
    /**
     * 요청 사용자
     */
    public function registerer()
    {
        return $this->belongsTo(\Api\Models\User::class, 'regist_user_id', 'user_id');
    }

    
    /**
     * review
     *
     * @return \Api\Models\Review
     */
    public function review_log()
    {
        return $this->belongsToMany(\Api\Models\Review::class, 'content_id', 'content_id');
    }

}
