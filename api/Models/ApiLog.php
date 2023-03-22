<?php

namespace Api\Models;

use Api\Models\LogModel;

/**
 * API 연계 시 요청 건에 대한 로깅
 * 
 * @property int $id 아이디
 * @property string $path API 경로
 * @property string $query API 쿼리
 * @property string $payload API Payload
 * @property string $user_id 사용자 아이디
 * @property string $status 처리 상태(A: 접수, S: 성공, F: 실패)
 * @property string $errors 오류 내용
 * @property \Carbon\Carbon $created_at 생성일시
 * @property \Carbon\Carbon $updated_at 수정일시
 */
class ApiLog extends LogModel
{
    protected $guarded = [];

    protected $casts = [];

    public $logging = false;
    /**
     * 요청 사용자
     */
    public function user()
    {
        return $this->belongsTo('Api\Models\User', 'user_id');
    }    
}
