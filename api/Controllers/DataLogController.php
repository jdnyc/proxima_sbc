<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Services\DataLogService;
use Psr\Container\ContainerInterface;


class DataLogController extends BaseController
{

    private  $dataLogService;

    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->dataLogService = new DataLogService($container);
    }

    /**
     * 데이터 로그 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function index(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();    
        $data = $this->dataLogService->list($input);
        return $response->ok($data);
    }
}
