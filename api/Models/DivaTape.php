<?php

namespace Api\Models;

use Api\Models\LogModel;
use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class DivaTape extends LogModel
{
    use SoftDeletes;

    protected $table = 'DP_TAPES';

    protected $primaryKey = 'id';

    const CREATED_AT = 'CREATED_AT';//'regist_dt';
    const UPDATED_AT = 'UPDATED_AT';//'updt_dt';
    const DELETED_AT = 'DELETED_AT';//'delete_dt';';

    public $sortable = ['id'];
    public $sort = 'id';
    public $dir = 'desc';

    public $logging = true;

    public static function findByTaId($taId)
    {
        return DivaTape::where('ta_id', $taId)->first();
    }
}
