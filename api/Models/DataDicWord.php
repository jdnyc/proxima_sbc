<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 *데이터 사전 용어 
 * 
 * @property int $id 아이디(pk)
 * @property int $no no 순번
 * @property int $domn_id 도메인 아이디
 * @property string $word_se 용어구분
 * @property string $word_nm 한글 용어명
 * @property string $word_eng_nm 영문 용어명
 * @property string $word_eng_abrv_nm 영문 약어명
 * @property string $sttus_code 상태코드 
 * @property string $dc 설명
 */
class DataDicWord extends BaseModel
{
    use SoftDeletes;

    protected $table = 'dd_word';

    const DELETED_AT = 'delete_dt';

    public $sortable = ['id', 'no', 'word_nm', 'word_eng_nm', 'word_eng_abrv_nm', 'sttus_code'];

    protected $fillable = [
        'no',
        'word_se',
        'word_nm',
        'word_eng_nm',
        'word_eng_abrv_nm',
        'sttus_code',
        'dc',
        'thema_relm',
        'tmpr_yn',
        'domn_id'
    ];

    /**
     * 등록자
     *
     * @return \Api\Models\User
     */
    public function registerer()
    {
        return $this->belongsTo(\Api\Models\User::class, 'regist_user_id', 'user_id');
    }

    /**
     * 수정자
     *
     * @return \Api\Models\User
     */
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
}
