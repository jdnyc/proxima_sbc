<?php

namespace Api\Models;

use Api\Models\BaseModel;

class DivaApi extends BaseModel
{
    protected $table = 'DIVA_API';

    protected $primaryKey = 'id';

    const CREATED_AT = null;//'regist_dt';
    const UPDATED_AT = null;//'updt_dt';
    const DELETED_AT = null;//'delete_dt';';

    public $sortable = ['id'];
    public $sort = 'id';
    public $dir = 'desc';

    public static function getToken()
    {
        return DivaApi::first()->token;
    }
    public static function setToken($token)
    {
        $divaApi = DivaApi::first();
        $divaApi->token = $token;
        $divaApi->save();
        return $divaApi;
    }
}
