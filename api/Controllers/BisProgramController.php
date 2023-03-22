<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Services\BisProgramService;
use Psr\Container\ContainerInterface;
use Api\Services\DTOs\BisProgramSearchParams;

class BisProgramController extends BaseController
{
    /**
     * 접근 권한 서비스
     *
     * @var \Api\Services\BisProgramService
     */
    private $bisProgramService;

    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->bisProgramService = new BisProgramService($container);
    }

    /**
     * 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function index(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $params = new BisProgramSearchParams($input);     
        $lists = $this->bisProgramService->list($params);        
        return $response->ok($lists);
    }

     /**
     * 단건 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function show(ApiRequest $request, ApiResponse $response, array $args)
    {
        $pgmId = $args['pgm_id'];
        $pgm = $this->bisProgramService->find($pgmId);
        return $response->ok($pgm);
    }

    /**
     * 검색 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function search(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $params = new BisProgramSearchParams($input);

        if( !is_null($params->pgm_nm) ){
            $lists = $this->bisProgramService->search('pgm_nm', $params->pgm_nm );
        }else if( !is_null($params->pgm_id) ){
            $lists = $this->bisProgramService->search('pgm_id', $params->pgm_id );
        }
    
        return $response->ok($lists);
    }
}
