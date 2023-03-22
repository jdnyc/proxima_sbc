<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Services\DataDicColumnService;
use Api\Services\DataDicDomainService;
use Psr\Container\ContainerInterface;
use Api\Services\DTOs\DataDicColumnDto;
use Api\Services\DTOs\DataDicColumnSearchParams;

class DataDicColumnController extends BaseController
{
    /**
     * 데이터사전 컬럼 서비스
     *
     * @var \Api\Services\DataDicColumnService
     */
    private $dataDicColumnService;

    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->dataDicColumnService = new DataDicColumnService($container);
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
        $params = new DataDicColumnSearchParams($input);
        $columns = $this->dataDicColumnService->list($params);

        return $response->ok($columns);
    }
    /**
     * 컬럼 단건 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function show(ApiRequest $request, ApiResponse $response, array $args)
    {
        $columnId = $args['column_id'];
        $column = $this->dataDicColumnService->find($columnId);
        return $response->ok($column);
    }

    /**
     * 테이블 별 컬럼 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function getColumnsByTableId(ApiRequest $request, ApiResponse $response, array $args)
    {
        $tableId = $args['table_id'];
        $input = $request->all();
    
        $columns = $this->dataDicColumnService->getColumnsByTableId($tableId,$request);

        $dataDicDomainSetService = new DataDicDomainService($this->container);

        foreach ($columns as $column) {
            if (($column->std_yn == 'Y')) {
                if (!is_null($column->field)) {

                    $domainId = $column->field->domn_id;
                    $domainName = $dataDicDomainSetService->getColumnsByDomainId($domainId);
                    $column->domain = $domainName;
                } else {
                    $column->domain = NULL;
                }
            } else {
                $column->domain = NULL;
            }
        }

        return $response->ok($columns);
    }

    /**
     * 필드별 컬럼 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function getColumnsByFieldId(ApiRequest $request, ApiResponse $response, array $args)
    {
        $fieldId = $request->input('field_id');

        // 표준 여부
        $stdYn = $request->input('std_yn');
        $columns = $this->dataDicColumnService->getColumnsByFieldId($fieldId, $stdYn);
        return $response->ok($columns);
    }

    /**
     * 컬럼 등록
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

        $dto = new DataDicColumnDto($data);

        $column = $this->dataDicColumnService->create($dto, $user);

        return $response->ok($column, 201);
    }

    /**
     * 컬럼 수정
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function update(ApiRequest $request, ApiResponse $response, array $args)
    {
        $columnId = $args['column_id'];
        $data = $request->all();
        $user = auth()->user();

        $dto = new DataDicColumnDto($data);

        $column = $this->dataDicColumnService->update($columnId, $dto, $user);
        return $response->ok($column);
    }

    /**
     * 컬럼 삭제
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function delete(ApiRequest $request, ApiResponse $response, array $args)
    {
        $columnId = $args['column_id'];
        $user = auth()->user();

        $this->dataDicColumnService->delete($columnId, $user);
        return $response->ok();
    }

    /**
     * 컬럼 복원
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function restore(ApiRequest $request, ApiResponse $response, array $args)
    {
        $columnId = $args['column_id'];
        $user = auth()->user();

        $this->dataDicColumnService->restore($columnId, $user);
        return $response->ok();
    }
}
