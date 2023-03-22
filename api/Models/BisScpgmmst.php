<?php

namespace Api\Models;

use Api\Models\BaseModel;



class BisScpgmmst extends BaseModel
{
   

    protected $table = 'bis_scpgmmst';

    
    protected $primaryKey = 'pgm_id';
    protected $keyType = 'String';

    const CREATED_AT = null;//'regist_dt';
    const UPDATED_AT = null;//'updt_dt';
    const DELETED_AT = null;//'delete_dt';';
    public $incrementing = false;
    public $sortable = ['pgm_id'];
    public $sort = 'pgm_id';
    public $dir = 'desc';
}
