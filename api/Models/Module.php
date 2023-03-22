<?php

namespace Api\Models;

use Api\Models\BaseModel;

/**
 * 작업 모듈
 * 
 * @property int $module_info_id 아이디(pk)
 * @property string $active 활성화 여부(1:활성화, 0:비활성화)
 * @property string $main_ip 메인 아이피 주소
 * @property string $sub_ip 보조 아이피 주소
 * @property string $description 설명
 * @property string $last_access 마지막 수정 시간? 
 */
class Module extends BaseModel
{
    protected $table = 'bc_module_info';

    protected $primaryKey = 'module_info_id';

    protected $guarded = [];
}
