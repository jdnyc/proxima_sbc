<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 데이터 사전 코드셋
 * 
 * @property int $id 아이디(pk)
 * @property string $code_set_nm 코드셋 명
 * @property string $code_set_code 코드셋 코드
 * @property string $dc 설명
 */
class DataDicCodeSet extends BaseModel
{
    use SoftDeletes;

    protected $table = 'dd_code_set';

    protected $dateFormat = 'YmdHis';
    protected $primaryKey = 'id';

    // protected $dates = [
    //     'delete_dt'
    // ];
    const DELETED_AT = 'delete_dt';
    public $sortable = ['code_set_nm', 'code_set_code'];

    protected $fillable = [
        'code_set_nm',
        'code_set_code',
        'code_set_cl',
        'dc'
    ];

    public function registerer()
    {
        return $this->belongsTo(\Api\Models\User::class, 'regist_user_id', 'user_id');
    }

    public function updater()
    {
        return $this->belongsTo(\Api\Models\User::class, 'updt_user_id', 'user_id');
    }

    public function codeItems()
    {
        return $this->hasMany(\Api\Models\DataDicCodeItem::class, 'code_set_id', 'id')
            ->orderBy('sort_ordr');
    }
    /**
     * 소분류
     *
     * @return void
     */
    public function codeItemsSclas()
    {
        return $this->hasMany(\Api\Models\DataDicCodeItem::class, 'code_set_id', 'id');
    }
    /**
     * 중분류
     *
     * @return void
     */
    public function codeItemsMlsfcDpl()
    {
        return $this->hasMany(\Api\Models\DataDicCodeItem::class, 'code_set_id', 'id')->where('dp', 1);
    }

    // /**
    //  * 데이터 사전 도메인
    //  *
    //  * @return \Api\Models\DataDicDomain
    //  */
    // public function domain()
    // {
    //     return $this->hasOne(\Api\Models\DataDicDomain::class, 'code_set_id', 'id');
    // }
}
