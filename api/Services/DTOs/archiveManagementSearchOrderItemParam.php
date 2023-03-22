<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

/**
 * @property string $keyword 오더 아이템 검색
 */
final class archiveManagementSearchOrderItemParam extends DataTransferObject
{
    public $keyword;
}
