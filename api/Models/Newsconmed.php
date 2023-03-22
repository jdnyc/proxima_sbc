<?php

namespace Api\Models;

use Api\Models\BaseModel;


class Newsconmed extends BaseModel
{
    protected $table = 'NEWSCONMED';

    protected $primaryKey = 'IDX';
    public $sort = 'IDX';


    const CREATED_AT = null;
    const UPDATED_AT = null;
    const DELETED_AT = null;
    protected $dateFormat = 'YmdHis';
    protected $fillable = [];
}
