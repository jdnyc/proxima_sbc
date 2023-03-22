<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Services\MaterialService;
use Psr\Container\ContainerInterface;

class MaterialController extends BaseController
{
    private $materialService;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->materialService = new MaterialService($container);
    }

    public function index(ApiRequest $request, ApiResponse $response, array $args)
    {
      
        $list = $this->materialService->list($request);
        return $response->ok($list);
    }

    public function scenes(ApiRequest $request, ApiResponse $response, array $args)
    {
        $list = $this->materialService->scenes($request);
        return $response->ok($list);
    }
}