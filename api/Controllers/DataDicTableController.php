<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Services\DataDicTableService;
use Psr\Container\ContainerInterface;
use Api\Services\DTOs\DataDicTableDto;
use Api\Services\DataDicCodeSetService;
use Api\Services\DataDicCodeItemService;
use Api\Services\DataDicColumnService;
use Api\Services\DTOs\DataDicTableSearchParams;

class DataDicTableController extends BaseController
{
    /**
     * 데이터사전 테이블 서비스
     *
     * @var \Api\Services\DataDicTableService
     */
    private $dataDicTableService;

    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->dataDicTableService = new DataDicTableService($container);
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
        $params = new DataDicTableSearchParams($input);
        $tables = $this->dataDicTableService->list($params);

        $dataDicCodeSetService = new DataDicCodeSetService($this->container);

        $selectCodeFields = ['id', 'code_itm_code', 'code_itm_nm'];
        $sysCodeItems = $dataDicCodeSetService->findByCodeOrFail('DD_SYSTEM')
            ->codeItems()
            ->get($selectCodeFields);
        $tableSectionCodeItems = $dataDicCodeSetService->findByCodeOrFail('DD_TABLE_SE')
            ->codeItems()
            ->get($selectCodeFields);

        foreach ($tables as $table) {
            $sysCode = $table->sys_code;
            $tableSection = $table->table_se;
            $table->system = DataDicCodeItemService::getCodeItemByCode($sysCodeItems, $sysCode);
            $table->table_section = DataDicCodeItemService::getCodeItemByCode($tableSectionCodeItems, $tableSection);
        }

        return $response->ok($tables);
    }

    /**
     * 테이블 단건 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function show(ApiRequest $request, ApiResponse $response, array $args)
    {
        $tableId = $args['table_id'];
        $table = $this->dataDicTableService->find($tableId);
        return $response->ok($table);
    }


    /**
     * 테이블 등록
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

        $dto = new DataDicTableDto($data);
        $dto->createValidate();

        $table = $this->dataDicTableService->create($dto, $user);
        return $response->ok($table, 201);
    }

    /**
     * 테이블 수정
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function update(ApiRequest $request, ApiResponse $response, array $args)
    {
        $tableId = $args['table_id'];
        $data = $request->all();
        $user = auth()->user();

        $dto = new DataDicTableDto($data);
        $dto->updateValidate();

        $table = $this->dataDicTableService->update($tableId, $dto, $user);
        return $response->ok($table);
    }

    /**
     * 테이블 삭제
     * 테이블 아이디를 공유하는 컬럼들도 같이 삭제
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function delete(ApiRequest $request, ApiResponse $response, array $args)
    {

        $tableId = $args['table_id'];
        $user = auth()->user();

        $dataDicColumnService = new DataDicColumnService($this->container);
        $dataDicColumnService->deleteColumnsByTableId($tableId);


        $this->dataDicTableService->delete($tableId, $user);
        return $response->ok();
    }

    /**
     * 테이블 복원
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function restore(ApiRequest $request, ApiResponse $response, array $args)
    {
        $tableId = $args['table_id'];
        $user = auth()->user();

        $this->dataDicTableService->restore($tableId, $user);
        return $response->ok();
    }
}
