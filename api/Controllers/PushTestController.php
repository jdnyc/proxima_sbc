<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Services\DTOs\ApiLogDto;
use Psr\Container\ContainerInterface;

class PushTestController extends BaseController
{
    /**
     * 테스트용
     *
     */


    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }
    public function index(ApiRequest $request, ApiResponse $response, array $args){
        // $query = ApiJobs::query();
        // $target = $query->first();
        return $response->ok();
    }
}
