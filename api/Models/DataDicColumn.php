<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 데이터 사전 컬럼
 * 
 * @property int $id 아이디(pk)
 * @property int $table_id 테이블ID
 * @property string $std_yn 표준여부
 * @property string $column_nm 컬럼명
 * @property string $column_eng_nm 영문 컬럼명
 * @property int $field_id DATA_DICARY_FIELD_ID
 * @property string $data_ty 데이터 타입
 * @property string $data_lt 데이터 길이
 * @property string $data_dcmlpoint 데이터 소수점
 * @property string $pk_yn pk여부
 * @property string $nn_yn Not Null여부
 * @property int $ordr 순서
 * @property int $sttus_code 상태코드
 * @property string $dc 설명
 */

class DataDicColumn extends BaseModel
{
    use SoftDeletes;

    protected $table = 'dd_column';

    protected $dateFormat = 'YmdHis';
    protected $primaryKey = 'id';

    // protected $dates = [
    //     'delete_dt'
    // ];
    const DELETED_AT = 'delete_dt';

    public $sortable = ['column_nm', 'column_eng_nm', 'domain'];

    protected $fillable = [
        'table_id',
        'std_yn',
        'column_nm',
        'column_eng_nm',
        'field_id',
        'data_ty',
        'data_lt',
        'data_dcmlpoint',
        'pk_yn',
        'nn_yn',
        'ordr',
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
     * 데이터 사전 필드
     *
     * @return \Api\Models\DataDicField
     */
    public function field()
    {
        return $this->belongsTo(\Api\Models\DataDicField::class, 'field_id', 'id');
    }
    /**
     * 데이터 사전 테이블
     *
     * @return \Api\Models\DataDicTable
     */
    public function table()
    {
        return $this->belongsTo(\Api\Models\DataDicTable::class, 'table_id', 'id');
    }
}
