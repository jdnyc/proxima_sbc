<?php

namespace Api\Models;

use Api\Models\BaseModel;


class Broaddsc extends BaseModel
{
    protected $table = 'BROADDSC';

    protected $primaryKey = 'ID_NO';
    public $sort = 'ID_NO';


    const CREATED_AT = null;
    const UPDATED_AT = null;
    const DELETED_AT = null;
    protected $dateFormat = 'YmdHis';
    protected $fillable = [];
}
