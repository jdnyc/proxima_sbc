<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

/**
 *  @property string $pgm_id 프로그램id
 */
class BisEpisodeSearchParams extends DataTransferObject
{
    public $pgm_id;
    public $epsd_id;    
    public $epsd_no;
    public $keyword;
}
