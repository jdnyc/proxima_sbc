<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 데이터 사전 테이블
 * 
 * @property int $id 아이디(pk)
 * @property string $sys_code 시스템코드
 * @property string $table_nm 테이블명
 * @property string $table_eng_nm 영문 테이블명
 * @property string $table_se 테이블 속성
 * @property int $sttus_code 상태코드
 * @property string $dc 설명
 */
class DataDicTable extends BaseModel
{
    use SoftDeletes;

    protected $table = 'dd_table';

    protected $dateFormat = 'YmdHis';
    protected $primaryKey = 'id';

    // protected $dates = [
    //     'delete_dt'
    // ];
    const DELETED_AT = 'delete_dt';

    public $sortable = ['system', 'table_nm', 'table_section'];

    protected $fillable = [
        'sys_code',
        'table_nm',
        'table_eng_nm',
        'table_se',
        'sttus_code',
        'dc',
    ];

    public function registerer()
    {
        return $this->belongsTo(\Api\Models\User::class, 'regist_user_id', 'user_id');
    }

    public function updater()
    {
        return $this->belongsTo(\Api\Models\User::class, 'updt_user_id', 'user_id');
    }

    public function columns()
    {
        return $this->hasMany(\Api\Models\DataDicColumn::class, 'table_id', 'id');
    }
}
