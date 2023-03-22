<?php

namespace Api\Models;

use Api\Models\BaseModel;

/**
 * 작업 모듈
 * 
 * @property int $task_type_id 아이디(pk)
 * @property string $type 스토리지 유형(FTP,SAN,NAS)
 * @property string $login_id 로그인 아이디
 * @property string $login_pw 로그인 암호
 * @property string $path 스토리지경로
 * @property string $name 스토리지명
 * @property string $group_name 스토리지 그룹명
 * @property string $mac_address 맥어드레스
 * @property string $authority 권한(r/w)
 * @property string $describe 절차
 * @property string $description 설명
 * @property string $virtual_path 웹서버 가상경로
 * @property string $path_for_mac 맥OS 경로
 * @property string $path_for_unix 유닉스(리눅스) 경로
 * @property int $read_limit 읽기 제한
 * @property int $write_limit 쓰기 제한
 * @property string $path_for_win 윈도우즈 경로
 * @property int $limit_session 세션 제한
 */
class Storage extends BaseModel
{
    protected $table = 'bc_storage';

    protected $primaryKey = 'storage_id';

    protected $guarded = [];
}
