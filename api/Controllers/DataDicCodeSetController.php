<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Services\DataDicCodeSetService;
use Psr\Container\ContainerInterface;
use Api\Services\DTOs\DataDicCodeSetDto;
use Api\Services\DataDicCodeItemService;
use Api\Services\DTOs\DataDicCodeSetSearchParams;

class DataDicCodeSetController extends BaseController
{
    /**
     * 데이터사전 테이블 서비스
     *
     * @var \Api\Services\DataDicCodeSetService
     */
    private $dataDicCodeSetService;

    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->dataDicCodeSetService = new DataDicCodeSetService($container);
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
        $params = new DataDicCodeSetSearchParams($input);
        $codeSets = $this->dataDicCodeSetService->list($params , $request->includes );

        //$codeSets->addRelationships();
        return $response->ok($codeSets);
    }

    /**
     *코드셋 단건 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function show(ApiRequest $request, ApiResponse $response, array $args)
    {

        $codeSetIdOrCode = $args['code_set_id'];



        if (is_null($codeSetIdOrCode) || $codeSetIdOrCode == null) {
            return $response->ok();
        } else {

            $isCode = (bool) $request->input('is_code');

            $codeSet = null;
            if (!$isCode) {
                $codeSet = $this->dataDicCodeSetService->findOrFail($codeSetIdOrCode);
            } else {
                $codeSet = $this->dataDicCodeSetService->findByCodeOrFail($codeSetIdOrCode);
            }
            $codeSet->addRelationships();
            return $response->ok($codeSet);
        }
    }

    /**
     *코드셋코드로 단건 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function getByCode(ApiRequest $request, ApiResponse $response, array $args)
    {
        $codeSetCode = $args['code_set_code'];
        $codeSet = $this->dataDicCodeSetService->findByCode($codeSetCode);
        return $response->ok($codeSet);
    }

    /**
     * 코드셋 등록
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function create(ApiRequest $request, ApiResponse $response, array $args)
    {
        $data = $request->all();
        $user = auth()->user();

        $dto = new DataDicCodeSetDto($data);

        $codeSet = $this->dataDicCodeSetService->create($dto, $user);
        return $response->ok($codeSet, 201);
    }

    /**
     * 코드셋 수정
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function update(ApiRequest $request, ApiResponse $response, array $args)
    {
        $codeSetId = $args['code_set_id'];
        $data = $request->all();
        $user = auth()->user();

        $dto = new DataDicCodeSetDto($data);

        $codeSet = $this->dataDicCodeSetService->update($codeSetId, $dto, $user);
        return $response->ok($codeSet);
    }

    /**
     * 코드셋 삭제
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function delete(ApiRequest $request, ApiResponse $response, array $args)
    {
        $codeSetId = $args['code_set_id'];
        $user = auth()->user();

        $dataDicCodeItemService = new DataDicCodeItemService($this->container);
        $dataDicCodeItemService->deleteCodeItemsByCodeSetId($codeSetId);

        $this->dataDicCodeSetService->delete($codeSetId, $user);
        return $response->ok();
    }

    /**
     * 코드셋 복원
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function restore(ApiRequest $request, ApiResponse $response, array $args)
    {
        $codeSetId = $args['code_set_id'];
        $user = auth()->user();

        $this->dataDicCodeSetService->restore($codeSetId, $user);
        return $response->ok();
    }
}
