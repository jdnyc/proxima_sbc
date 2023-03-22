<?php

namespace Api\Models\Fs;

use Api\Models\LogModel;

class FileServer extends LogModel
{
    protected $table = 'fs_file_servers';

    protected $guarded = [];

    protected $casts = [
        'port' => 'integer'
    ];

    public function jobs()
    {
        return $this->hasMany(\Api\Models\Fs\Job::class, 'job_id', 'id');
    }
}
