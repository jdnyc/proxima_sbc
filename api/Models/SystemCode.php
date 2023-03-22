<?php

namespace Api\Models;

use Api\Models\BaseModel;

/**
 * 시스템 코드
 * 
 * @property int $ID 테이블고유ID
 * @property string $CODE 코드
 * @property string $CODE_NM 코드명(값)
 * @property int $TYPE_ID 코드유형ID
 * @property int $SORT 정렬
 * @property string $USE_YN 사용여부
 * @property string $MEMO 설명
 * @property string $REF1 추가항목1
 * @property string $REF2 추가항목2
 * @property string $REF3 추가항목3
 * @property string $REF4 추가항목4
 * @property string $REF5 추가항목5
 * @property string $CREATED_DATE 생성일시
 * @property int $CREATED_USER 생성자
 * @property string $UPDATED_DATE 수정일시
 * @property int $UPDATED_USER 수정자
 * @property string $CODE_NM_ENGLISH code name (english)
 */
class SystemCode extends BaseModel
{
    protected $table = 'bc_sys_code';

    protected $primaryKey = 'id';

    protected $guarded = [];
}

