<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

/**
 * @property int $code_set_id 코드셋 아이디
 * @property string $domn_mlsfc 중분류
 * @property string $domn_sclas 소분류
 * @property string $domn_ty 도메인 타입
 * @property string $domn_eng_nm 도메인 영문명
 * @property string $domn_nm 도메인 명
 * @property  string $data_ty 데이터 타입
 * @property string $data_lt 데이터 길이
 * @property string $data_dcmlpoint 도메인 소수점
 * @property string $sttus_code 상태 코드 
 * @property string $keyword 추가,수정 할때 도메인 검색 키워드 
 * @property string $dc 설명 
 */

final class DataDicDomainDto extends DataTransferObject
{
    public $code_set_id;
    public $domn_mlsfc;
    public $domn_sclas;
    public $domn_ty;
    public $domn_eng_nm;
    public $domn_nm;
    public $data_ty;
    public $data_lt;
    public $data_dcmlpoint;
    public $sttus_code;
    public $keyword;
    public $dc;
}
