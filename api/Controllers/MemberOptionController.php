<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Services\MemberOptionService;
use Psr\Container\ContainerInterface;
use Illuminate\Database\Capsule\Manager as DB;

class MemberOptionController extends BaseController
{
    /**
     * 접근 권한 서비스
     *
     * @var \Api\Services\MemberOptionService
     */
    private $memberOptionService;

    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->memberOptionService = new MemberOptionService($container);
    }

    /**
     * 컬럼 순서 저장
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function columnSave(ApiRequest $request, ApiResponse $response, array $args)
    {
        $data = $request->all();
        $user = auth()->user();
        
        $memberOption = $this->memberOptionService->columnSave($data,$user);
        
        // $findSortOrder = $this->contentListColumnSortOrderService->findByUserId($user);
        
        // if(!is_null($findSortOrder)){
        //     $sortOrder = $this->contentListColumnSortOrderService->update($data,$user);            
        // }else{
        //     $sortOrder = $this->contentListColumnSortOrderService->create($data, $user);
        // };
        return $response->ok($memberOption, 201);
    }

}
