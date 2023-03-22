<?php

namespace Api\Models\Fs;

use Api\Models\LogModel;

class Job extends LogModel
{
    protected $table = 'fs_jobs';

    protected $guarded = [];

    protected $casts = [
        'filesize' => 'string',
        'progress' => 'integer',
        'priority' => 'integer',
        'file_server_id' => 'integer',
        'transferred' => 'string',
        'metadata' => 'array',
        'content_id' => 'integer',
        'file_id' => 'integer'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'started_at',
        'finished_at',
    ];

    public function log()
    {
        return $this->hasMany(\Api\Models\Fs\JobLog::class, 'job_id', 'id');
    }

    public function history()
    {
        return $this->hasMany(\Api\Models\Fs\JobHistory::class, 'job_id', 'id');
    }

    public function fileServer()
    {
        return $this->belongsTo(\Api\Models\Fs\FileServer::class, 'file_server_id', 'id');
    }

    public function file()
    {
        return $this->belongsTo(\Api\Models\File::class, 'file_id', 'id');
    }

    public function content()
    {
        return $this->belongsTo(\Api\Models\Content::class, 'content_id', 'content_id');
    }
    public function usrMeta()
    {
        return $this->hasOne(\Api\Models\ContentUsrMeta::class ,'usr_content_id' ,'content_id' );
    }
}
