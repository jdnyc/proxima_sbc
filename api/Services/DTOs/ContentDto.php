<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;
use Respect\Validation\Validator as v;

/**
 * 콘텐츠 DTO
 * 
 * @property int $content_id id
 */
final class ContentDto extends DataTransferObject
{
    public $content_id;
    public $category_id;
    public $category_full_path;
    public $bs_content_id;
    public $ud_content_id;
    public $title;
    public $parent_content_id;
    // public $is_deleted;
    // public $is_hidden;
    //public $reg_user_id;
    public $expired_date;
    public $last_modified_date;
    public $updated_at;
    public $created_date;
    public $status;
}