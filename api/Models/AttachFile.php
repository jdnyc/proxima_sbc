<?php

namespace Api\Models;

use Api\Traits\FileTrait;
use Illuminate\Http\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttachFile extends Model
{
    use SoftDeletes;
    use FileTrait;

    protected $fillable = ['filepath', 'filesize', 'org_name'];
}