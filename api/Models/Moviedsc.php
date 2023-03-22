<?php

namespace Api\Models;

use Api\Models\BaseModel;


class Moviedsc extends BaseModel
{
    protected $table = 'MOVIEDSC';

    protected $primaryKey = null;
    public $sort = null;


    const CREATED_AT = null;
    const UPDATED_AT = null;
    const DELETED_AT = null;
    protected $dateFormat = 'YmdHis';
    protected $fillable = [];
}
