<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

/**
 * @property string $keyword 콘텐츠 타이틀 검색
 * @property string $mediaId 콘텐츠 미디어 아이디 검색
 * @property Array $ud_content_id
 */
final class contentSearchParam extends DataTransferObject
{
    public $keyword;
    public $mediaId;
    public $ud_content_id;
    
    public $is_deleted;
}
