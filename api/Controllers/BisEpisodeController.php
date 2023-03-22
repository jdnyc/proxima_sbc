<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Services\BisEpisodeService;
use Psr\Container\ContainerInterface;
use Api\Services\DTOs\BisEpisodeSearchParams;

class BisEpisodeController extends BaseController
{
    /**
     * 접근 권한 서비스
     *
     * @var \Api\Services\BisEpisodeService
     */
    private $bisEpisodeService;

    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->bisEpisodeService = new BisEpisodeService($container);
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
        $params = new BisEpisodeSearchParams($input);     
        $lists = $this->bisEpisodeService->list($params);        
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
        $pgmEpisodeId = $args['pgm_id_epsd_no'];
        $pgm = $this->bisEpisodeService->find($pgmEpisodeId);
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
    public function getEpisodesByPgmId(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $pgm_id = $args['pgm_id'];
        $params = new BisEpisodeSearchParams($input);
        $lists = $this->bisEpisodeService->searchByPgmId($pgm_id, $params );
    
        return $response->ok($lists);
    }
}
