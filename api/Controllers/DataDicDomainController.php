<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Services\DataDicDomainService;
use Psr\Container\ContainerInterface;
use Api\Services\DTOs\DataDicDomainDto;
use Api\Services\DataDicCodeSetService;
use Api\Services\DataDicCodeItemService;
use Api\Services\DTOs\DataDicDomainSearchParams;

class DataDicDomainController extends BaseController
{
    /**
     * 데이터사전 도메인 서비스
     *
     * @var \Api\Services\DataDicDomainService
     */
    private $dataDicDomainService;

    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->dataDicDomainService = new DataDicDomainService($container);
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
        $params = new DataDicDomainSearchParams($input);
        $domains = $this->dataDicDomainService->list($params);

        // 코드 아이템 가져오기
        $dataDicCodeSetService = new DataDicCodeSetService($this->container);
        $selectCodeFields = ['id', 'code_itm_code', 'code_itm_nm', 'dp'];
        // 도메인 타입
        $sysCodeItems = $dataDicCodeSetService->findByCodeOrFail('DD_DOMN_TY')
            ->codeItems()
            ->get($selectCodeFields);
        // 도메인 중분류
        $MlsfcCodeItems = $dataDicCodeSetService->findByCodeOrFail('DD_DOMN_CL')
            ->codeItems()
            ->get($selectCodeFields);

        // 도메인 소분류
        $SclasCodeItems = $dataDicCodeSetService->findByCodeOrFail('DD_DOMN_CL')
            ->codeItems()
            ->get($selectCodeFields);

        foreach ($domains as $domain) {
            $domainTypeName = $domain->domn_ty;
            $domain->domain_type = DataDicCodeItemService::getCodeItemByCode($sysCodeItems, $domainTypeName);
        }
        foreach ($domains as $domain) {
            $domainMiddleClassification = $domain->domn_mlsfc;
            $domain->domain_mlsfc = DataDicCodeItemService::getCodeItemByCodeMlsfc($MlsfcCodeItems, $domainMiddleClassification);
        }
        foreach ($domains as $domain) {
            $domainSmallClassification = $domain->domn_sclas;
            if (!($domainSmallClassification == null)) {
                $domain->domain_sclas = DataDicCodeItemService::getCodeItemByCodeSclas($SclasCodeItems, $domainSmallClassification);
            }
        }

        return $response->ok($domains);
    }

    /**
     * 도메인 단건 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function show(ApiRequest $request, ApiResponse $response, array $args)
    {
        $domainId = $args['domain_id'];
        $domain = $this->dataDicDomainService->find($domainId);
        return $response->ok($domain);
    }

    /**
     * 도메인 등록
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

        $dto = new DataDicDomainDto($data);

        $domain = $this->dataDicDomainService->create($dto, $user);
        return $response->ok($domain, 201);
    }

    /**
     * 도메인 수정
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function update(ApiRequest $request, ApiResponse $response, array $args)
    {
        $domainId = $args['domain_id'];
        $data = $request->all();
        $user = auth()->user();

        $dto = new DataDicDomainDto($data);

        $domain = $this->dataDicDomainService->update($domainId, $dto, $user);
        return $response->ok($domain);
    }

    /**
     * 도메인 삭제
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function delete(ApiRequest $request, ApiResponse $response, array $args)
    {
        $domainId = $args['domain_id'];
        $user = auth()->user();

        $this->dataDicDomainService->delete($domainId, $user);
        return $response->ok();
    }

    /**
     * 도메인 복원
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function restore(ApiRequest $request, ApiResponse $response, array $args)
    {
        $domainId = $args['domain_id'];
        $user = auth()->user();

        $this->dataDicDomainService->restore($domainId, $user);
        return $response->ok();
    }
}
