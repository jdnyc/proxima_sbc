<?php

namespace Api\Controllers;

use Api\Http\ApiResponse;
use Api\Http\ApiRequest;
use Api\Services\AuthorityMandateService;
use Api\Services\UserService;
use Psr\Container\ContainerInterface;

class AuthorityMandateController extends BaseController
{
    /**
     * 권한 승계 서비스
     * 
     * @var \Api\Services\AuthorityMandateService
     */
    private $authorityMandateService;

    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->authorityMandateService = new AuthorityMandateService($container);
        
        $this->hasAdmin = auth()->user()->hasAdminGroup();
        
    }

    /**
     * 권한 위임자 목록 조회
     * 권한 위임자가 수임자들 목록 조회
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function getMandataryListByMandator(ApiRequest $request, ApiResponse $response, array $args)
    {
        $user = auth()->user();
        $hasAdmin = $this->hasAdmin;
        $data = $request->all();
        $mandates = $this->authorityMandateService->getMandataryListByMandator($data,$user,$hasAdmin);

        // 수임자 유저 정보
        return $response->ok($mandates);
    }

    /**
     * 권한 승계 수임자 등록 
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function create(ApiRequest $request, ApiResponse $response, array $args)
    {
        $data = $request->all();
        $user = auth()->user();
        $mandate = $this->authorityMandateService->create($data, $user);
        return $response->ok($mandate, 201);
    }
    /**
     * 권한 승계 수정
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function update(ApiRequest $request, ApiResponse $response, array $args)
    {
        $authorityMandateId = $args['authority_mandate_id'];
        $data = $request->all();
        $user = auth()->user();
 
        $hasAdmin = $this->hasAdmin;
      
        $mandate = $this->authorityMandateService->update($authorityMandateId, $data, $user, $hasAdmin);
     
        if($mandate){
            return $response->ok($mandate);
        }else{
            return $response->error('위임자만이 수정할 수 있습니다.');
        }
        
    }
    /**
     * 권한 승계 삭제
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function delete(ApiRequest $request, ApiResponse $response, array $args)
    {
        $authorityMandateId = $args['authority_mandate_id'];
        
        $user = auth()->user();
        $hasAdmin = $this->hasAdmin;
        $mandate = $this->authorityMandateService->delete($authorityMandateId, $user,$hasAdmin);
        if($mandate){
            return $response->ok();
        }else{
            return $response->error('위임자만이 삭제할 수 있습니다.');
        }
    }
}
