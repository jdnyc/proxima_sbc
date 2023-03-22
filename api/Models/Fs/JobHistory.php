<?php

namespace Api\Models\Fs;

use Api\Models\LogModel;

class JobHistory extends LogModel
{
    protected $table = 'fs_job_history';

    protected $guarded = [];

    protected $casts = [
        'port' => 'integer'
    ];

    public function job()
    {
        return $this->hasMany(\Api\Models\Fs\Job::class, 'job_id', 'id');
    }
}
