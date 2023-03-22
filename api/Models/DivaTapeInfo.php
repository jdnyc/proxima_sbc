<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class DivaTapeInfo extends BaseModel
{
    //use SoftDeletes;
    protected $connection = 'diva';
    protected $table = 'DP_OBJECT_TAPE_INFOS';

    protected $primaryKey = 'of_id';

    const CREATED_AT = null;// 'CREATED_AT';//'regist_dt';
    const UPDATED_AT = null;//'UPDATED_AT';//'updt_dt';
    const DELETED_AT = null;//'DELETED_AT';//'delete_dt';';

    public $sortable = ['of_id'];
    public $sort = 'of_id';
    public $dir = 'desc';
}
