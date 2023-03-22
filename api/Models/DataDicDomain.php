<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 데이터 사전 도메인
 * 
 * @property int $id 아이디(pk)
 * @property string $domn_mlsfc 중분류
 * @property string $domn_sclas 소분류
 * @property string $domn_ty 도메인 타입
 * @property string $domn_eng_nm 도메인 영문명
 * @property int $domn_nm 도메인 명
 * @property string $data_ty 데이터 타입
 * @property string $data_lt 데이터 길이
 * @property string $data_dcmlpoint 도메인 소수점
 * @property string $sttus_code 상태 코드 
 * @property string $dc 설명
 */
class DataDicDomain extends BaseModel
{
    use SoftDeletes;

    protected $table = 'dd_domain';

    protected $dateFormat = 'YmdHis';
    protected $primaryKey = 'id';

    // protected $dates = [
    //     'delete_dt'
    // ];
    const DELETED_AT = 'delete_dt';

    public $sortable = ['domn_mlsfc', 'domn_sclas', 'domain_type', 'domn_nm', 'domn_eng_nm'];

    protected $fillable = [
        'sys_code',
        'domn_nm',
        'domn_eng_nm',
        'domn_se',
        'sttus_code',
        'dc',
        'code_set_id'
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
     * 데이터사전 표준용어
     *
     * @return \Api\Models\DataDicWord[]
     */
    public function words()
    {
        return $this->hasMany(\Api\Models\DataDicWord::class, 'domn_id', 'id');
    }

    /**
     * 데이터사전 필드
     *
     * @return \Api\Models\DataDicWord[]
     */
    public function fields()
    {
        return $this->hasMany(\Api\Models\DataDicField::class, 'domn_id', 'id');
    }

    /**
     * 데이터사전 코드셋
     *
     * @return \Api\Models\DataDicCodeSet
     */
    public function codeSet()
    {
        return $this->hasOne(\Api\Models\DataDicCodeItem::class, 'code_set_id', 'id');
    }

    /**
     * 데이터사전 코드 아이템
     *
     * @return \Api\Models\DataDicCodeItem[]
     */
    public function codeItems()
    {
        return $this->hasMany(\Api\Models\DataDicCodeItem::class, 'code_set_id', 'code_set_id');
    }
}
