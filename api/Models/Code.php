<?php

namespace Api\Models;

use Api\Models\CodeType;
use Api\Models\BaseModel;

/**
 * 코드
 * 
 * @property int $id 아이디(pk)
 * @property string $code 코드유형코드
 * @property string $name 코드유형명
 * @property int $code_type_id 코드유형아이디
 * @property int $sort 정렬순서
 * @property int $hidden 숨김여부(0:보임(default), 1:숨김)
 * @property string $ename 코드 영문명 
 * @property string $ref1 BC_SYS_CODE와 연계하기 위한 코드값
 * @property string $other 한글, 영어 이외의 언어명
 * @property string $ref2
 * @property string $ref3
 * @property string $ref4
 * @property string $ref5
 * @property string $use_yn 사용여부(Y:사용, N:미사용)
 */
class Code extends BaseModel
{
    protected $table = 'bc_code';

    protected $primaryKey = 'id';

    protected $fillable = [
        'code',
        'name',
        'code_type_id',
        'sort',
        'hidden',
        'ename',
        'ref1',
        'other',
        'ref2',
        'ref3',
        'ref4',
        'ref5',
        'use_yn',
    ];

    public function codeType()
    {
        return $this->belongsTo(CodeType::class, 'code_type_id', 'id');
    }
}
