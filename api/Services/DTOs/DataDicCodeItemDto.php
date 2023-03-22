<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

/**
 * @property int $code_set_id 코드셋 ID
 * @property string $code_itm_nm 코드항목 명
 * @property string $code_itm_code 코드항목 코드
 * @property int $sort_ordr 순서
 * @property string $root parent 구분
 * @property int $parnts_id parnt_id 구분
 * @property string $dc 설명
 * @property int $dp
 * @property string $code_path
 * @property string $changeCodeItems 인코딩된 변경될 아이템 객체들
 * @property string $action 구분
 */

final class DataDicCodeItemDto extends DataTransferObject
{
    public $code_set_id;
    public $code_itm_nm;
    public $code_itm_code;
    public $sort_ordr;
    public $use_yn;
    public $parnts_id;
    public $dp;
    public $code_path;
    public $root;
    public $dc;
}
