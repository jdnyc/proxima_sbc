<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

/**
 * @property string $field_nm 시스템명    
 * @property string $field_eng_nm 테이블명
 * @property int $domn_id 영문 테이블명
 * @property string $sttus_code 상태코드
 * @property string $dc 설명
 */

final class DataDicFieldDto extends DataTransferObject
{

    public $field_nm;
    public $field_eng_nm;
    public $domn_id;
    public $sttus_code;
    public $dc;
}
