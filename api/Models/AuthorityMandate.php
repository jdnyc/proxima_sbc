<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 권한 승계 테이블
 * 
 * @property int $id 아이디(pk)
 * @property string $MANDATOR 위임자
 * @property string $MANDATARY 수인자
 */

class AuthorityMandate extends BaseModel
{
    use SoftDeletes;

    protected $table = 'authority_mandate';

    const DELETED_AT = 'delete_dt';

    // 위임자 유저 정보
    public function mandatorInfo()
    {
        return $this->belongsTo(\Api\Models\User::class, 'mandator', 'user_id');
    }
    // 수임자 유저 정보
    public function mandataryInfo()
    {
        return $this->belongsTo(\Api\Models\User::class, 'mandatary', 'user_id');
    }
}
