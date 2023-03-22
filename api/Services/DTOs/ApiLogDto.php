<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;
use Respect\Validation\Validator as v;

/**
 * ApiLog DTO
 * 
 * @property string $name API 명
 * @property string $path API 경로
 * @property string $query API 쿼리
 * @property string $user_id 사용자 아이디
 * @property string $payload API Payload
 * @property string $status 처리 상태(A: 접수, S: 성공, F: 실패)
 * @property string|null $errors 오류 내용
 */
final class ApiLogDto extends DataTransferObject
{
    public $name;
    public $path;
    public $query;
    public $user_id;
    public $payload;
    public $status;
    public $errors;
}
