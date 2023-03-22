<?php

namespace Api\Models;

use Api\Models\BaseModel;


class Newsmas extends BaseModel
{
    protected $table = 'NEWSMAS';

    protected $primaryKey = 'IDX';
    public $sort = null;


    const CREATED_AT = null;
    const UPDATED_AT = null;
    const DELETED_AT = null;
    protected $dateFormat = 'YmdHis';
    protected $fillable = [];
}
