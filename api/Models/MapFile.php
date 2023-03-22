<?php

namespace Api\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * 파일정보
 * 
 */
class MapFile extends Model
{
    protected $guarded = [];

    protected $casts = [];

    protected $fillable = [  
        'file_key',
        'user_id',
        'remote_ip',
        'content_id',
        'task_id'
    ];
}
