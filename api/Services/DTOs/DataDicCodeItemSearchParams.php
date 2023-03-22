<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

/**  
 * @property string $keyword 색 키워드
 * @property bool $is_deleted 삭제 데이터 조회 여부(Y or N)
 * @property int $code_set_id 코드셋 아이디
 */

class DataDicCodeItemSearchParams extends DataTransferObject
{
    public $domn_id;
    public $keyword;
    public $is_deleted;
    public $code_set_id;
    public $sorters;
}
