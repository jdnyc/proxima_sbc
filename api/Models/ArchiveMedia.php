<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 아카이브 미디어
 * 
 * @property int $id 아이디(pk)
 */

class ArchiveMedia extends BaseModel
{
    use SoftDeletes;

    protected $table = 'archive_medias';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    
    protected $dateFormat = 'YmdHis';

    protected $fillable = [
        'content_id',
        'media_id',
        'object_name' ,
        'archive_category' ,
        'archive_group' ,
        'qos' ,
        'destinations',
        'user_id'
    ];

    
    public function registerer()
    {
        return $this->belongsTo(\Api\Models\User::class, 'user_id', 'user_id');
    }
}
