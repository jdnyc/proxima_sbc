<?php

namespace Api\Models;

use Api\Types\JobStatus;
use Illuminate\Database\Eloquent\Model;

class ApiJob extends Model
{
    protected $fillable = [
        'owner_id', 'status', 'priority', 'result', 'headers', 'progress',
        'payload', 'task_id', 'api_server_ip', 'type', 'url', 'method',
    ];

    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
        'result' => 'array',
        'errors' => 'array',
    ];

    public function isQueued()
    {
        return ($this->status === JobStatus::QUEUED);
    }

    public function isAssigning()
    {
        return ($this->status === JobStatus::ASSIGNING);
    }

    public function isAssigned()
    {
        return ($this->status === JobStatus::ASSIGNED);
    }

    public function isWorking()
    {
        return ($this->status === JobStatus::WORKING);
    }

    public function isFailed()
    {
        return ($this->status === JobStatus::FAILED);
    }

    public function isDone()
    {
        return ($this->status === JobStatus::FINISHED);
    }

    public function isCanceled()
    {
        return ($this->status === JobStatus::CANCELED);
    }
}
