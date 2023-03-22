<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 데이터 사전 필드
 * 
 * @property int $id 아이디(pk)
 * @property string $field_nm 필드명
 * @property string $field_eng_nm 영문 필드명
 * @property string $domn_id 도메인 ID
 * @property int $sttus_code 상태코드
 * @property string $dc 설명
 */
class DataDicField extends BaseModel
{
    use SoftDeletes;

    protected $table = 'dd_field';

    protected $dateFormat = 'YmdHis';
    protected $primaryKey = 'id';

    // protected $dates = [
    //     'delete_dt'
    // ];
    const DELETED_AT = 'delete_dt';

    public $sortable = ['field_nm', 'field_eng_nm'];

    protected $fillable = [
        'field_nm',
        'field_eng_nm',
        'domn_id',
        'sttus_code',
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
    /**
     * 데이터 사전 도메인
     *
     * @return \Api\Models\DataDicDomain
     */
    public function domain()
    {
        return $this->belongsTo(\Api\Models\DataDicDomain::class, 'domn_id', 'id');
    }
    // /**
    //  * 데이터사전 컬럼
    //  *
    //  * @return \Api\Models\DataDicColumn[]
    //  */
    // public function columns()
    // {
    //     return $this->hasMany(\Api\Models\DataDicColumn::class, 'field_id', 'id');
    // }
}
