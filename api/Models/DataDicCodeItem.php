<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 데이터 사전 컬럼
 * 
 * @property int $id 아이디(pk)
 * @property int $code_set_id 코드셋 ID
 * @property string $code_itm_nm 코드항목 명
 * @property string $code_itm_code 코드항목 코드
 * @property string $sort_ordr 순서
 * @property string $use_yn 사용여부
 * @property string $dc 설명
 */

class DataDicCodeItem extends BaseModel
{
    use SoftDeletes;

    protected $table = 'dd_code_item';

    protected $dateFormat = 'YmdHis';
    protected $primaryKey = 'id';


    // protected $dates = [
    //     'delete_dt'
    // ];
    const DELETED_AT = 'delete_dt';

    public $sortable = ['code_itm_code', 'code_itm_nm'];

    protected $fillable = [
        'code_set_id',
        'code_itm_nm',
        'code_itm_code',
        'parnts_id',
        'sort_ordr',
        'use_yn',
        'dc',
        'code_path',
        'dp'
    ];

    public function registerer()
    {
        return $this->belongsTo(\Api\Models\User::class, 'regist_user_id', 'user_id');
    }

    public function updater()
    {
        return $this->belongsTo(\Api\Models\User::class, 'updt_user_id', 'user_id');
    }
    /**
     * 데이터 사전 코드셋
     *
     * @return \Api\Models\DataDicCodeSet
     */
    public function codeSet()
    {
        return $this->belongsTo(\Api\Models\DataDicCodeSet::class, 'code_set_id', 'id');
    }
    /**
     * 데이터 사전 domain
     *
     * @return \Api\Models\DataDicDomain
     */
    public function domain()
    {
        return $this->belongsTo(\Api\Models\DataDicDomain::class, 'code_set_id', 'code_set_id');
    }
}
