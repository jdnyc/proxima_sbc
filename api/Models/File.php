<?php

namespace Api\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 파일정보
 * 
 */
class File extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'file_size' => 'integer',
        'storage_id' => 'integer',
        'media_id' => 'integer',
        'content_id' => 'integer'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'expired_at'
    ];

    protected $fillable = [
        'file_root',
        'file_path',
        'file_name',
        'file_ext',
        'ori_file_name',
        'file_size',
        'storage_id',
        'status',
        'media_id',
        'content_id',
        'expired_at'
    ];

    public function media()
    {
        return $this->belongsTo(\Api\Models\Media::class, 'media_id', 'media_id');
    }

    public function content()
    {
        return $this->belongsTo(\Api\Models\Content::class, 'content_id', 'content_id');
    }

    public function fileServerJobs()
    {
        return $this->hasMany(\Api\Models\Fs\Job::class, 'file_id', 'id');
    }
}
