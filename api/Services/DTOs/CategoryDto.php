<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;
use Respect\Validation\Validator as v;

/**
 * 데이터 사전 표준용어 DTO
 * 
 * @property int $no 순번
 * @property string $word_se 표준용어 구분(STDLNG: 표준어, SYNONM:동의어)
 * @property string $word_nm 표준용어명
 * @property string $word_eng_nm 표준용어 영문명
 * @property string $word_eng_abrv_nm 테이블 속성
 * @property string $sttus_code 상태코드

 * @property string $dc 설명
 * @property string $thema_relm 주제영역
 * @property string $tmpr_yn 임시여부
 * @property string $domn_id 도메인 아이디
 */
final class CategoryDto extends DataTransferObject
{
    public $category_id;
    public $parent_id;
    public $category_title;
    public $code;
    public $show_order;
    public $no_children;
    public $extra_order;
    public $is_deleted;
    public $category_path;
    public $dep;
}
