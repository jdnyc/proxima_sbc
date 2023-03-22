<?php

namespace Api\Models;

use Api\Models\BaseModel;

/**
 * 아카이브 작업 추가 정보
 * 
 * @property int $id 아이디(pk)
 */

class ArchiveTask extends BaseModel
{
  
    protected $table = 'archive';
    const CREATED_AT = null;
    const UPDATED_AT = null;
    const DELETED_AT = null;
    protected $primaryKey = 'archive_seq';
    
    protected $dateFormat = 'YmdHis';

    protected $fillable = [
    ];
}
