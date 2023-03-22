<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * BIS 프로그램 조회
 * @property int 
 * @property string 
 */
class BisCode extends BaseModel
{
    //use SoftDeletes;
    protected $connection = 'bis';

    protected $table = 'zbasecode';

    protected $primaryKey = 'hcode,dcode';
    protected $keyType = 'string';
    const CREATED_AT = null;//'regist_dt';
    const UPDATED_AT = null;//'updt_dt';
    const DELETED_AT = null;//'delete_dt';';

    public $sortable = ['dcode'];
    public $sort = 'dcode';
    public $dir = 'desc';
    

    // protected $fillable = [   
    // ];
}
