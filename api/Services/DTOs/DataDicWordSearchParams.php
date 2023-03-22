<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

/**
 *  @property string $keyword 검색 키워드
 *  @property bool $is_deleted 삭제 데이터 조회 여부(Y or N)
 *  @property string $listAll 페이지네이션 동작안하게 전체 리스트 뽑기
 */
class DataDicWordSearchParams extends DataTransferObject
{
    public $keyword;
    public $listAll;
    public $is_deleted;
    public $sttus_code;
}
