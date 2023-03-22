<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Services\CategoryService;
use Psr\Container\ContainerInterface;

class CategoryController extends BaseController
{
    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->categoryService = new CategoryService($container);
    }

    public function getCategoryByHierarchy(ApiRequest $request, ApiResponse $response, array $args){
      
        $codeItems = [];
  
        $parentId = $request->input('node');
        $selId =  $request->input('selId');
     
        $codes = $this->categoryService->listAll($parentId);

        $codeItems = $this->categoryService->makeNodes($codes, $selId);
  
        return $response->okArray($codeItems);
      }
}
