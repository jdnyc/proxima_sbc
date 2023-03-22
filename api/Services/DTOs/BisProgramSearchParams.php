<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

/**
 *  @property string $pgm_id 프로그램id
 *  @property string $pgm_nm 프로그램명
 * @property string $pgm_nm 프로그램명
 */
class BisProgramSearchParams extends DataTransferObject
{
    public $pgm_id;
    public $pgm_nm;
    public $dvs_yn;
    public $use_yn;
}
