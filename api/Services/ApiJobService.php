<?php

namespace Api\Services;

use Api\Core\HttpClient;
use Api\Models\ApiJob;
use Api\Services\BaseService;
use Api\Types\JobStatus;

/**
 * 포털 홈페이지 배포 및 동기화 서비스
 */
class ApiJobService extends BaseService
{

    public function __construct()
    {
    }

    /**
     * 잡 목록 조회
     *
     * @param string $type
     * @param array $status
     * @param integer $limit
     * @param integer $start
     * @return void
     */
    function list($type, $statuses = [], $limit = 10, $start = 0) {

        $query = ApiJob::query();
        $query->where('type', $type);
        if (empty($statuses)) {
            $statuses[] = JobStatus::QUEUED;
        }
        $query->whereIn('status', $statuses);
        $query->orderBy('id', 'ASC');

        // dump($query->sort);
        // dump($query->dir);
        // return $query->get();
        return paginate($query, $start, $limit);
    }

    /**
     * 생성
     *
     * @param array $input 생성 데이터
     * @return \Api\Models\ApiJobs 생성된 테이블 객체
     */
    public function create($data)
    {
        $apiJob = new ApiJob($data);
        $apiJob->save();

        return $apiJob;
    }

    /**
     * 작업상태 할당중으로 변경
     *
     * @param \Api\Models\ApiJobs $apiJob
     * @return \Api\Models\ApiJobs
     */
    public function assigning($apiJob)
    {
        $apiJob->status = JobStatus::ASSIGNING;
        $apiJob->save();
        return $apiJob;
    }

    /**
     * 작업상태 실패로 변경
     *
     * @param \Api\Models\ApiJobs $apiJob
     * @param string $error
     * @return \Api\Models\ApiJobs
     */
    public function failed($apiJob, $error)
    {
        $apiJob->status = JobStatus::FAILED;
        $apiJob->errors = [$error];
        $apiJob->save();
        return $apiJob;
    }

    /**
     * 작업상태 작업중으로 변경
     *
     * @param \Api\Models\ApiJobs $apiJob
     * @param string $error
     * @return \Api\Models\ApiJobs
     */
    public function working($apiJob)
    {
        $apiJob->status = JobStatus::WORKING;
        $apiJob->save();
        return $apiJob;
    }

    /**
     * 작업상태 할당완료로 변경
     *
     * @param \Api\Models\ApiJobs $apiJob
     * @return \Api\Models\ApiJobs
     */
    public function assigned($apiJob)
    {
        $apiJob->status = JobStatus::ASSIGNED;
        $apiJob->save();
        return $apiJob;
    }

    /**
     * 잡 스케줄 수행??
     *
     * @return void
     */
    public function excute($type, $statuses = [], $limit = 10, $start = 0)
    {
        $lists = $this->list($type, $statuses , $limit, $start );

        // "scheme" => "http"
        // "host" => "127.0.0.1"
        // "path" => "/api/v1/push-test/209"
        // "query" => "dfsfd=dfsfdds"
        if (!empty($lists)) {
            foreach ($lists as $list) {
                $id = $list->id;
                $method = $list->method;
                $urlInfo = parse_url($list->url);
                $baseUrl = $urlInfo['scheme'] . '://' . $urlInfo['host'];
                if ($urlInfo['port']) {
                    $baseUrl = $baseUrl . ':' . $urlInfo['port'];
                }
                $url = $urlInfo['path'];
                $params = [];

                if ($urlInfo['query']) {
                    $params = $urlInfo['query'];
                }

                $options = [
                    'headers' => $list->headers,
                ];

                if (!empty($list->payload)) {
                    $options['body'] = json_encode($list->payload);
                }
                $now = new \Carbon\Carbon();
                $list->started_at = $now->format('YmdHis');
                $list->status = JobStatus::WORKING;
                $list->save();
                //시작전 저장

                $client = new HttpClient($baseUrl);
                $result = $client->request($method, $url, $params, $options);

                $apiJob = ApiJob::find($id);
                $now = new \Carbon\Carbon();
                $apiJob->finished_at = $now->format('YmdHis');

                $error = $client->isError();

                if (!$error) {
                    //정상
                    // dump('body');
                    // dump( $client->getBody() );
                    // dump('con');
                    // dump( $client->getBody()->getContents() );
                    $resultJson = $result->getContents();
                    $apiJob->result = $resultJson;
                    $apiJob->status = JobStatus::FINISHED;
                } else {
                    //오류
                    //dump( $error );
                    $resultJson = $error->request . ' ' . $error->response;
                    $apiJob->retry_count = $list->retry_count + 1;
                    $apiJob->result = $resultJson;
                    $apiJob->status = JobStatus::FAILED;
                }
                $apiJob->save();
            }
        }

    }

    /**
     * 동기화 잡 생성
     *
     * @param $type api 타입 , 서비스명
     * @param $method restFul 함수
     * @param $payload array
     * @param $id 수정 삭제 id
     * @return \Api\Models\ApiJob
     */
    public function createApiJob($type, $method, $payload, $id = null)
    {
        $headers = [
            'content-type' => 'application/json',
            'X-API-KEY' => 'B+Hqhy*3GEuJJmk%',
            'X-API-USER' => 'admin',
        ];

        $publish = config('publish');
        $baseUrl = trim($publish['portal_url'], '/');
        $urlMap = [
            'Api\Services\DataDicCodeSetService' => [
                'post' => 'ap/restful/codeSet',
                'put' => 'ap/restful/codeSet',
                'delete' => 'ap/restful/codeSet',
                'headers' => [
                    'content-type' => 'application/json',
                ],
            ],
            'Api\Services\DataDicCodeItemService' => [
                'post' => 'ap/restful/codeItm',
                'put' => 'ap/restful/codeItm' ,
                'delete' => 'ap/restful/codeItm',
                'headers' => [
                    'content-type' => 'application/json',
                ],
            ],
            'Api\Services\ContentService' => [
                'post' => 'ap/restful/contents',
                'put' => 'ap/restful/contents',
                'delete' => 'ap/restful/contents',
                'headers' => [
                    'content-type' => 'application/json',
                ],
            ],
        ];

        $headers = $urlMap[$type]['headers'] ?? null;
        if ($headers) {
            $headers = $urlMap[$type]['headers'];
        }

        switch ($method) {
            case 'create':
                $method = 'post';
                break;
            case 'update':
                $method = 'post';
                break;
            case 'delete':
                $method = 'post';
                break;
        }

        $url = $baseUrl . '/' . $urlMap[$type][$method];

        $status = JobStatus::QUEUED;

        $data = [
            'owner_id' => $id,
            'type' => $type,
            'status' => $status,
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'payload' => $payload,
        ];

        $this->create($data);

        return true;
    }
}
