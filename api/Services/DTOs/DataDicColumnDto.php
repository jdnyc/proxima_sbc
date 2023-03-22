<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

/**
 *  @property int $table_id 테이블ID
 *  @property string $std_yn 표준여부
 *  @property string $column_nm 컬럼명
 *  @property string $column_eng_nm 영문 컬럼명
 *  @property int $field_id DATA_DICARY_FIELD_ID
 *  @property string $data_ty 데이터 타입
 *  @property string $data_lt 데이터 길이
 *  @property string $data_dcmlpoint 데이터 소수점
 *  @property string $pk_yn pk여부
 *  @property string $nn_yn Not Null여부
 *  @property int $ordr 순서
 *  @property string $sttus_code 상태코드
 *  @property string $domn_nm 도메인 이름
 *  @property string $standard 표준정보
 *  @property string $nonStandard 비표준정보
 *  @property string $dc 설명
 */

final class DataDicColumnDto extends DataTransferObject
{
    public $table_id;
    public $std_yn;
    public $column_nm;
    public $column_eng_nm;
    public $field_id;
    public $data_ty;
    public $data_lt;
    public $data_dcmlpoint;
    public $pk_yn;
    public $nn_yn;
    public $ordr;
    public $sttus_code;
    public $domn_nm;
    public $standard;
    public $nonStandard;
    public $dc;
}
