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
final class DataDicWordDto extends DataTransferObject
{

    public $no;
    public $word_se;
    public $word_nm;
    public $word_eng_nm;
    public $word_eng_abrv_nm;
    public $sttus_code;
    public $dc;
    public $thema_relm;
    public $tmpr_yn;
    public $domn_id;

    /**
     * 표준용어 생성 시 정합성 체크
     *
     * @return bool
     */
    public function createValidate()
    {
        $validator = v::attribute('word_se', v::stringType()->length(1, 100))
            ->attribute('word_nm', v::stringType()->length(1, 100))
            ->attribute('word_eng_nm', v::stringType()->length(1, 300))
            ->attribute('word_eng_abrv_nm', v::stringType()->length(1, 100))
            ->attribute('sttus_code', v::stringType()->length(1, 100), false)
            ->attribute('dc', v::optional(v::stringType()->length(0, 3000)), false);

        return $validator->assert($this);
    }

    /**
     * 표준용어 업데이트 시 정합성 체크
     *
     * @return bool
     */
    public function updateValidate()
    {
        $validator = v::attribute('word_se', v::optional(v::stringType()->length(1, 100)), false)
            ->attribute('word_nm', v::optional(v::stringType()->length(1, 100)), false)
            ->attribute('word_eng_nm', v::optional(v::stringType()->length(1, 300)), false)
            ->attribute('word_eng_abrv_nm', v::optional(v::stringType()->length(1, 100)), false)
            ->attribute('sttus_code', v::optional(v::stringType()->length(1, 100, false)), false)
            ->attribute('dc', v::optional(v::stringType()->length(0, 3000)), false);

        return $validator->assert($this);
    }
}
