<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Models\Social\Category;
use Api\Models\Social\Platform;
use Psr\Container\ContainerInterface;

class PlatformController extends BaseController
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
     * 플랫폼 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function index(ApiRequest $request, ApiResponse $response, array $args)
    {
        $platforms = Platform::all();

        return response()->ok($platforms);
    }
    
    /**
     * 유튜브 카테고리 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function getCategories(ApiRequest $request, ApiResponse $response, array $args)
    {
        $categories = Category::all();

        return response()->ok($categories);
    }
}
