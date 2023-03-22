<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 사용자 계정 신청
 * 
 * @property int $id 아이디(pk)
 * @property int $dept 부서
 * @property int $user_id 신청자 아이디
 * @property string $mnfct_se 제작구분
 * @property string $progrm 프로그램 명
 * @property string $charger_id 담당자 아이디
 * @property string $password 비밀번호
 * @property string $phone 전화번호
 * @property string $lxtn_no 내선번호
 * @property string $use_purps 사용목적
 * @property \Api\Types\MemberStatus $status 상태
 * @property string $delete_dt 삭제일시
 * @property string $updt_dt 수정일시
 * @property string $user_nm 사용자명
 * @property string $progrm_id 프로그램 아이디
 * @property string $compt_dt 승인일시
 * @property \Api\Types\MemberStatus $pd_status 담당 승인 상태
 * @property string $instt 부처
 */
class MemberRequest extends BaseModel
{
    use SoftDeletes;

    protected $table = 'member_request';

    const DELETED_AT = 'delete_dt';

    protected $dates = [
        'compt_dt'
    ];
    
    /**
     * 담당자
     *
     * @return \Api\Models\User
     */
    public function charger()
    {
        return $this->belongsTo(\Api\Models\User::class, 'charger_id', 'user_id');
    }
    /**
     * 사용자 신청 프로그램
     *
     * @return \Api\Models\MemberRequestProgram
     */
    public function programs()
    {
        return $this->hasMany(\Api\Models\MemberRequestProgram::class, 'member_request_id', 'id');
    }
}
