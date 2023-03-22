<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;
use Respect\Validation\Validator as v;

/**
 * 콘텐츠 DTO
 * 
 * @property int $media_id id
 */
final class MediaDto extends DataTransferObject
{
    public $content_id;
    public $storage_id;
    public $media_type;
    public $path;
    public $filesize;
    public $created_date;
    public $reg_type;
    public $status;
    public $delete_date;
    public $flag;
    public $delete_status;
    public $vr_start;
    public $vr_end;
    public $expired_date;
}