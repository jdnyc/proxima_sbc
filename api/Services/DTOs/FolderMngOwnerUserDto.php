<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

/**
 * 폴더 관리 오너 유저 DTO
 * @property int $folder_id;
 * @property string $user_id;
 */
final class FolderMngOwnerUserDto extends DataTransferObject
{
    public $folder_id;
    public $user_id;
}