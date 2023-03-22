<?php

namespace Api\Controllers;

use Api\Models\ApiJob;
use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Types\ApiJobType;
use Api\Services\SnsPostService;
use Psr\Container\ContainerInterface;

class ApiJobController extends BaseController
{
    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        // db 커넥션 연결
        $container->get('db');
    }

    /**
     * API 작업 업데이트
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function update(ApiRequest $request, ApiResponse $response, array $args)
    {
        $id = $args['api_job_id'] ?? null;

        if($id === null) {
            return;
        }

        $apiJob = ApiJob::find($id);
        if($apiJob === null) {
            api_abort_404(ApiJob::class);
        }

        if($apiJob->type === ApiJobType::SNS_PUBLISH) {
            $input  = $request->all();
            $apiJob->status = $input['status'] ?? '';
            if(isset($input['progress'])) {
                $apiJob->progress = $input['progress'];
            }

            if(isset($input['result'])) {
                $apiJob->result = $input['result'];
            }

            $apiJob->save();

            $snsPostService = new SnsPostService($this->container);
            $snsPostService->updateStatus($apiJob);
        } else {
            response()->ok();
        }
        
    }
}
