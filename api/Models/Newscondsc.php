<?php

namespace Api\Models;

use Api\Models\BaseModel;


class Newscondsc extends BaseModel
{
    protected $table = 'NEWSCONDSC';

    protected $primaryKey = null;
    public $sort = null;


    const CREATED_AT = null;
    const UPDATED_AT = null;
    const DELETED_AT = null;
    protected $dateFormat = 'YmdHis';
    protected $fillable = [];
}
