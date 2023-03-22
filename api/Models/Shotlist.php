<?php

namespace Api\Models;

use Api\Models\BaseModel;


class Shotlist extends BaseModel
{
    protected $table = 'bc_shot_list';

    protected $primaryKey = 'list_id';
    public $sort = 'list_id';


    const CREATED_AT = null;
    const UPDATED_AT = null;
    const DELETED_AT = null;
    // protected $dateFormat = 'YmdHis';
    protected $fillable = [];
}
