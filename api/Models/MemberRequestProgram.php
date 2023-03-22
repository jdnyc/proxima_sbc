<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 프로그램 신청
 * 
 * @property string $member_request_id 제작구분
 * @property string $folder_path_nm 프로그램
 * @property string $folder_path 아이디
 * @property string $pgm_id 비밀번호
 */
class MemberRequestProgram extends BaseModel
{
    protected $table = 'member_request_program';

    const CREATED_AT = null;
    const UPDATED_AT = null;

}
