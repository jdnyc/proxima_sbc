<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

/**
 * @property string $code_set_nm 코드셋 명
 * @property string $code_set_code 코드셋 코드
 * @property string $dc 설명
 * @property string $code_set_cl 분류정보
 */

final class DataDicCodeSetDto extends DataTransferObject
{
    public $code_set_nm;
    public $code_set_code;
    public $dc;
    public $code_set_cl;
}
