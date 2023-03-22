<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Services\DataDicFieldService;
use Psr\Container\ContainerInterface;
use Api\Services\DTOs\DataDicFieldDto;
use Api\Services\DTOs\DataDicFieldSearchParams;

class DataDicFieldController extends BaseController
{
    /**
     * 데이터사전 필드 서비스
     *
     * @var \Api\Services\DataDicFieldService
     */
    private $dataDicFieldService;

    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->dataDicFieldService = new DataDicFieldService($container);
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
        $params = new DataDicFieldSearchParams($input);
        $fields = $this->dataDicFieldService->list($params);
        return $response->ok($fields);
    }

    /**
     * 필드 단건 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function show(ApiRequest $request, ApiResponse $response, array $args)
    {
        $fieldId = (int) $args['field_id'];
        $field = $this->dataDicFieldService->find($fieldId);
        return $response->ok($field);
    }

    /**
     * 필드 등록
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

        $dto = new DataDicFieldDto($data);

        $field = $this->dataDicFieldService->create($dto, $user);

        return $response->ok($field, 201);
    }

    /**
     * 필드 수정
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function update(ApiRequest $request, ApiResponse $response, array $args)
    {
        $fieldId = $args['field_id'];
        $data = $request->all();
        $user = auth()->user();

        $dto = new DataDicFieldDto($data);

        $field = $this->dataDicFieldService->update($fieldId, $dto, $user);
        return $response->ok($field);
    }

    /**
     * 필드 삭제
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function delete(ApiRequest $request, ApiResponse $response, array $args)
    {
        $fieldId = $args['field_id'];
        $user = auth()->user();

        $this->dataDicFieldService->delete($fieldId, $user);
        return $response->ok();
    }

    /**
     * 필드 복원
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function restore(ApiRequest $request, ApiResponse $response, array $args)
    {
        $fieldId = $args['field_id'];
        $user = auth()->user();

        $this->dataDicFieldService->restore($fieldId, $user);
        return $response->ok();
    }

    /**
     *  이름과 동일한 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function searchByName(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $params = new DataDicFieldSearchParams($input);

        $keyword =  $params->keyword;

        $lists = $this->dataDicFieldService->searchByName($keyword)->toArray();
        return $response->ok($lists);
    }
}
