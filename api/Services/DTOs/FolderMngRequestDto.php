<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;
use Respect\Validation\Validator as v;

/**
 * 제작 폴더 신청 관리
 * 
 * @property int $no 순번
 * @property string $word_se 표준용어 구분(STDLNG: 표준어, SYNONM:동의어)
 * @property string $word_nm 표준용어명
 * @property string $word_eng_nm 표준용어 영문명
 * @property string $word_eng_abrv_nm 테이블 속성
 * @property string $sttus_code 상태코드

 * @property string $dc 설명
 * @property string $thema_relm 주제영역
 * @property string $tmpr_yn 임시여부
 * @property string $domn_id 도메인 아이디
 */

/**
 * 제작 폴더 신청 관리
 *@property int $category_id
 *@property string $chmod
 *@property string $created_at
 *@property string $cursize
 *@property int $cursize_num
 *@property string $dc
 *@property string $deleted_at
 *@property string $dvs_yn
 *@property string $expired_date
 *@property string $folder_path
 *@property string $folder_path_nm
 *@property string $fs_type
 *@property string $fsname
 *@property int $grace_period
 *@property string $grace_period_unit
 *@property string $group_cd
 *@property int $hardlimit_num
 *@property int $id
 *@property string $ntcn_yn
 *@property string $owner_cd
 *@property int $parent_id
 *@property string $pgm_id
 *@property int $quota
 *@property string $quota_unit
 *@property string $regist_user_id
 *@property int $softlimit_num
 *@property string $status
 *@property int $step
 *@property string $updated_at
 *@property string $updt_user_id
 *@property string $use_yn
 *@property string $using_yn
 */
final class FolderMngRequestDto extends DataTransferObject
{
    public $id;
    public $folder_path;
    public $folder_path_nm;
    public $group_cd;
    public $quota;
    public $chmod;
    
    public $owner_cd;
    public $quota_unit;
    public $grace_period;
    public $grace_period_unit;
    public $expired_date;
    public $dc;
    public $ntcn_yn;

    public $parent_id; 
    public $step;

    public $pgm_id;
    public $using_yn;
    public $category_id;

    public $use_yn;
    public $dvs_yn;
}
