<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

/**
 *  @property string $keyword 검색 키워드
 *  @property string $code 코드 셋 코드
 *  @property bool $is_deleted  삭제 데이터 조회 여부(Y or N)
 */
class DataDicCodeSetSearchParams extends DataTransferObject
{
    public $keyword;
    public $code;
    public $is_deleted;
}
