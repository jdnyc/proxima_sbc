<?php

namespace Api\Services;

use Api\Models\ApiLog;
use Api\Models\User;
use Api\Services\BaseService;
use Api\Services\DTOs\ApiLogDto;

class ApiLogService extends BaseService
{
    /**
     * 목록 조회
     *
     * @return \Illuminate\Support\Collection
     */
    public function list()
    {
        $query = ApiLog::query();
        $lists = paginate($query);
        return $lists;
    }

     /**
     * 상세 조회
     *
     * @param mixed $id
     * @return \Api\Models\ApiLog 모델 객체
     */
    public function find(int $id)
    {
        $apiLog = ApiLog::find($id);
        return $apiLog;
    }

    /**
     * 생성
     *
     * @param array $input 생성 데이터     
     * @return \Api\Models\Media 생성된 테이블 객체
     */
    public function create(ApiLogDto $dto)
    {       
        $apiLog = new ApiLog();
        $apiLog->path = $dto->path;
        $apiLog->query = $dto->query;
        $apiLog->user_id = $dto->user_id;
        $apiLog->payload = $dto->payload;
        $apiLog->status = $dto->status;

        if(!empty($dto->errors)) {
            $apiLog->errors = $dto->errors;
        }

        $apiLog->save();

        return $apiLog;
    }

    /**
     * 성공 처리
     *
     * @param \Api\Models\ApiLog $apiLog
     * @return \Api\Models\ApiLog
     */
    public function succeed(ApiLog $apiLog)
    {
        $apiLog->status = 'S';
        $apiLog->save();
        
        return $apiLog;
    }

    /**
     * 실패 처리
     *
     * @param \Api\Models\ApiLog $apiLog
     * @param string $errors
     * @return \Api\Models\ApiLog
     */
    public function fail(ApiLog $apiLog, string $errors = null)
    {
        $apiLog->status = 'F';
        if(!empty($errors)) {
            $apiLog->errors = $errors;
        }
        $apiLog->save();
        
        return $apiLog;
    }

}
