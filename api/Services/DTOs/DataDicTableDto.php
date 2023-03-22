<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;
use Respect\Validation\Validator as v;

/**
 * 데이터 사전 DTO
 * 
 * @property string $sys_code 시스템 코드
 * @property string $table_nm 테이블명
 * @property string $table_eng_nm 영문 테이블명
 * @property string $table_se 테이블 속성
 * @property string $sttus_code 상태 코드
 * @property string $dc 설명
 */
final class DataDicTableDto extends DataTransferObject
{
    public $sys_code;
    public $table_nm;
    public $table_eng_nm;
    public $table_se;
    public $sttus_code;
    public $dc;

    /**
     * 테이블 생성 시 정합성 체크
     *
     * @return bool
     */
    public function createValidate()
    {
        $validator = v::attribute('sys_code', v::stringType()->length(1, 100))
            ->attribute('table_nm', v::stringType()->length(1, 100))
            ->attribute('table_eng_nm', v::stringType()->length(1, 100))
            ->attribute('table_se', v::stringType()->length(1, 100))
            ->attribute('sttus_code', v::stringType()->length(1, 100))
            ->attribute('dc', v::stringType()->length(0, 1000));

        return $validator->assert($this);
    }

    /**
     * 테이블 업데이트 시 정합성 체크
     *
     * @return bool
     */
    public function updateValidate()
    {
        $validator = v::attribute('sys_code', v::stringType()->length(1, 100))
            ->attribute('table_nm', v::stringType()->length(1, 100))
            ->attribute('table_eng_nm', v::stringType()->length(1, 100))
            ->attribute('table_se', v::stringType()->length(1, 100))
            ->attribute('sttus_code', v::stringType()->length(1, 100))
            ->attribute('dc', v::stringType()->length(0, 1000));

        return $validator->assert($this);
    }
}
