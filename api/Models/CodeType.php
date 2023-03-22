<?php

namespace Api\Models;

use Api\Models\Code;
use Api\Models\BaseModel;

/**
 * 코드 유형
 * 
 * @property int $id 아이디(pk)
 * @property string $code 코드유형코드
 * @property string $name 코드유형명
 * @property string $ref1 참조값
 */
class CodeType extends BaseModel
{
    protected $table = 'bc_code_type';

    protected $primaryKey = 'id';

    protected $fillable = [
        'code',
        'name',
        'ref1',
    ];

    public function sysCodes()
    {
        return $this->hasMany(Code::class, 'code_type_id', 'id');
    }
    // public function tableSe()
    // {
    //     return $this->hasMany(Code::class, 'code_type_id', 'id');
    // }
}
