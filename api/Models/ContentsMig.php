<?php

namespace Api\Models;

namespace Api\Models;

use Api\Models\DataLog;
use Illuminate\Database\Eloquent\Model;

/**
 * 아카이브 미디어
 * 
 * @property int $id 아이디(pk)
 */

class ContentsMig extends Model
{


    protected $table = 'contents_mig';
    const CREATED_AT = null;
    const UPDATED_AT = null;
    const DELETED_AT = null;
    protected $primaryKey = 'id';
    
    protected $dateFormat = 'YmdHis';

    protected $fillable = [
        'content','id'
    ];
}
