<?php

namespace Api\Services;

use Api\Core\HttpClient;
use Api\Models\ApiJob;
use Api\Models\DataDicCodeSet;
use Api\Services\BaseService;
use Api\Types\ApiJobType;
use Api\Types\JobStatus;

/**
 * 포털 홈페이지 배포 및 동기화 서비스
 */
class ApiSyncService extends BaseService
{
    const POTAL_BASE_URL = 'http://127.0.0.1/';

    const CODE_SYNC_URL = '/api/v1/push-test';

    public function __construct()
    {
    }

    /**
     * 코드 동기화 생성
     *
     * @param $payload
     * @return \Api\Models\ApiJob
     */
    public function createCodeSyncApiJob($codeTypeCode = null)
    {
        $apiJob = new ApiJob();
        $apiJob->type = ApiJobType::CODE_SYNC;
        $apiJob->status = JobStatus::QUEUING;
        $apiJob->save();

        $query = DataDicCodeSet::query();
        //$query->onlyTrashed();
        $query->with('codeItems');
        if (!empty($codeTypeCode)) {
            $query->where('code_type_code', $codeTypeCode);
        }
        $query->orderBy('id', 'asc');
        $codes = $query->get();
        $payload = $codes->toJson();

        //보내기 전 업데이트
        $apiJob->payload = $payload;
        $apiJob->status = JobStatus::QUEUED;
        $apiJob->save();

        $options = [
            'headers' => [
                'content-type' => 'application/json',
                'X-API-KEY' => 'B+Hqhy*3GEuJJmk%',
                'X-API-USER' => 'admin',
            ],
            'body' => $payload,
        ];

        $client = new HttpClient(ApiSyncService::POTAL_BASE_URL);
        $result = $client->post(ApiSyncService::CODE_SYNC_URL, $options);

        $resultJson = json_encode($result->getContents());

        //응답 후 업데이트
        if ($resultJson) {
            $apiJob->result = $resultJson;
            $apiJob->status = JobStatus::FINISHED;
        } else {
            $apiJob->result = $resultJson;
            $apiJob->status = JobStatus::FAILED;
        }
        $apiJob->save();

        return true;
    }
}
