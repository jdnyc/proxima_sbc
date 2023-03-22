<?php

namespace Api\Models;

use Api\Models\BaseModel;


class Newsdsc extends BaseModel
{
    protected $table = 'NEWSDSC';

    protected $primaryKey = null;
    public $sort = null;


    const CREATED_AT = null;
    const UPDATED_AT = null;
    const DELETED_AT = null;
    protected $dateFormat = 'YmdHis';
    protected $fillable = [];
}
