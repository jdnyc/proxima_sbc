<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Types\DomainType;
use Psr\Container\ContainerInterface;
use Api\Services\DataDicDomainService;
use Api\Services\DataDicCodeSetService;
use Api\Services\DataDicCodeItemService;
use Api\Services\OrderSalesPriceService;
use Api\Services\DTOs\DataDicCodeItemDto;
use Api\Services\DTOs\DataDicCodeItemSearchParams;
use Api\Models\DataDicDomain;

class DataDicCodeItemController extends BaseController
{
    /**
     * 데이터사전 CodeItem 서비스
     *
     * @var \Api\Services\DataDicCodeItemService
     */
    private $dataDicCodeItemService;

    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->dataDicCodeItemService = new DataDicCodeItemService($container);
    }

    /**
     * 코드아이템 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function index(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $params = new DataDicCodeItemSearchParams($input);
        $codeItems = $this->dataDicCodeItemService->list($params);

        return $response->ok($codeItems);
    }

        /**
     * 코드아이템 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function codeItemsByCodeSetId(ApiRequest $request, ApiResponse $response, array $args)
    {
        
        $offset = $request->start;
        $limit = $request->limit;
        $codeSetId = $args['code_set_id'];
        $input = $request->all();
        $params = new DataDicCodeItemSearchParams($input);
        
        $query = $this->dataDicCodeItemService->listByCodeSetId($params);
        $total = $query->get()->count();
        $codeItems = $query->offset($offset)->limit($limit)->get();
        
        $res = [
            'success' => true
        ];
        
        $res['data'] = $codeItems;
        $res['total'] = $total;
        return response()->withJson($res)
        ->withStatus(200);
    }

    /**
     * 코드아이템 단건 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function show(ApiRequest $request, ApiResponse $response, array $args)
    {
        $codeItemId = $args['code_item_id'];
        $codeItem = $this->dataDicCodeItemService->find($codeItemId);
        return $response->ok($codeItem);
    }
    /**
     * 코드셋 아이디로 코드 아이템 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function getCodeItemsByCodeSetId(ApiRequest $request, ApiResponse $response, array $args)
    {
        $codeSetId = $args['code_set_id'];
        
        $isCode = (bool) $request->input('is_code');
        // 중분류
        $isDp = (bool) $request->input('dp');
        // 소분류
        $isParntsId = (bool) $request->input('parnts_id');

        $codeItems = [];
        if (!$isCode) {
            // 코드셋 번호로 코드 아이템 찾기
            $codeItems = $this->dataDicCodeItemService->getCodeItemsByCodeSetId($codeSetId);
        } else {
            // 코드셋 코드로 코드 아이템 찾기
            $dataDicCodeSetService = new DataDicCodeSetService($this->container);
            $codeSet = $dataDicCodeSetService->findByCode($codeSetId);

            if ($codeSet) {
                if ($isDp) {
                    // 중분류 목록
                    $mlsfcDp = $request->input('dp');
                    $codeItems = $codeSet->codeItemsMlsfcDpl;
                } elseif ($isParntsId) {
                    //중분류 에서 선택된값 id로 찾는 아이템 목록(소분류)
                    $parntsId = $request->input('parnts_id');

                    foreach ($codeSet->codeItemsSclas as $codeItem) {
                        if ($codeItem->parnts_id == $parntsId) {
                            $codeItems = $codeItem;
                        }
                    }
                    // $codeItems = $codeSet->codeItemsSclas->where('parnts_id', $parntsId);
                } else {
                    // 코드셋 코드로 찾은 아이템 목록

                    /**
                     * 원래 안되던 부분
                     */
                    // $codeItems = $codeSet->codeItems->where('use_yn', 'Y');
                    $codeSetId = $codeSet->id;
                    $codeItems = $this->dataDicCodeItemService->getCodeItemsByCodeSetId($codeSetId);


                    /**
                     * 가격관리 코드를 호출할때는 코드마다 가격들까지 같이 호출
                     */
                    // if ($codeSet->code_set_code == "OR_PRICE") {
                    //     foreach ($codeItems as $codeItem) {

                    //         $orderSalesPriceService = new OrderSalesPriceService($this->container);
                    //         $method = $codeItem->code_itm_code;
                    //         $prices = $orderSalesPriceService->getorderSalesPriceListByMethod($method)->get();

                    //         $codeItem->prices = $prices;
                    //     };
                    // };
                    // if ($codeItems->code_itm_nm == 'DVD') {
                    // dd($codeItems->code_item_nm);
                    // }
                }
            }
        }



        return $response->ok($codeItems);
    }
    public function getCodeItemsByCodeSetCode(ApiRequest $request, ApiResponse $response, array $args)
    {
        $dataDicCodeSetService = new DataDicCodeSetService($this->container);

        $codeItems = [];

        $codeSetcode = $args['code_set_code'];
        $codeSets = explode(',', $codeSetcode);
        foreach ($codeSets as $key => $val) {
            $codeSet = $dataDicCodeSetService->findByCode($val);
            $codes = $codeSet->codeItems->where('use_yn', 'Y');
            $codeArr = [];
            foreach ($codes as $code) {
                $codeArr[] = $code;
            }
            $codeItems[] = array(
                'items' => $codeArr,
                'type' => $val
            );
        };

        return $response->ok($codeItems);
    }

    public function getCodeItemsByHierarchy(ApiRequest $request, ApiResponse $response, array $args){
      $dataDicCodeSetService = new DataDicCodeSetService($this->container);

      $codeItems = [];

      $codeSetCode = $args['code_set_code'];
      $parentId = $request->input('node');
      $selId =  $request->input('selId');
   
      $codeSet = $dataDicCodeSetService->findByCode($codeSetCode);
      $codes = $this->dataDicCodeItemService->getCodeItemsByCodeSetId($codeSet->id);
      //dd( $codes->toArray());

     /// $codeItems = $this->dataDicCodeItemService->makeNodes($codeItems, 0, $codes);
      $codeItems = $this->dataDicCodeItemService->makeNodes($codes, $selId);

      return $response->okArray($codeItems);
    }
    /**
     * 코드아이템 등록
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

        $dto = new DataDicCodeItemDto($data);

        $CodeItem = $this->dataDicCodeItemService->create($dto, $user);

        $CodeItem->code_path = $CodeItem->code_path . "/" . $CodeItem->id;
        $CodeItem->save();

        return $response->ok($CodeItem, 201);
    }

    /**
     * 코드아이템 수정
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function update(ApiRequest $request, ApiResponse $response, array $args)
    {
        $codeItemId = $args['code_item_id'];
        $data = $request->all();
        $user = auth()->user();

        $dto = new DataDicCodeItemDto($data);
        $keys = array_keys($data);
        $dto = $dto->only(...$keys);

        $codeItem = $this->dataDicCodeItemService->update($codeItemId, $dto, $user);
        return $response->ok($codeItem);
    }

    /**
     * 코드아이템 삭제
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function delete(ApiRequest $request, ApiResponse $response, array $args)
    {
        $codeItemId = $args['code_item_id'];
        $user = auth()->user();

        $this->dataDicCodeItemService->delete($codeItemId, $user);
        return $response->ok();
    }

    /**
     * 코드아이템 복원
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function restore(ApiRequest $request, ApiResponse $response, array $args)
    {
        $codeItemId = $args['code_item_id'];
        $user = auth()->user();

        $this->dataDicCodeItemService->restore($codeItemId, $user);
        return $response->ok();
    }

    /**
     * 도메인 별 코드 아이템 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function getCodesByDomainId(ApiRequest $request, ApiResponse $response, array $args)
    {
        $offset = $request->start;
        $limit = $request->limit;
        $domainId = (int)$request->input('domn_id');
        $dataDicDomainService = new DataDicDomainService($this->container);
        
        $domain = $dataDicDomainService->findOrFail($domainId);
        
        if ($domain->domn_ty == DomainType::CODE) {          
            // $codeItems = $this->dataDicCodeItemService->getCodeItemsByCodeSetId($domain->code_set_id);
            $query = \Illuminate\Database\Capsule\Manager::table('dd_code_item')
            ->whereNull("delete_dt")
            ->whereRaw('code_set_id = ? CONNECT BY PRIOR ID=DECODE(ID,PARNTS_ID,NULL,PARNTS_ID) START WITH PARNTS_ID=0 ORDER SIBLINGS BY SORT_ORDR, ID', [$domain->code_set_id]);
            $total = $query->get()->count();
            
            $codeItems = $query->offset($offset)->limit($limit)->get();
            
            
            $dataDicCodeSetService = new DataDicCodeSetService($this->container);
            $codeSet = $dataDicCodeSetService->find($domain->code_set_id);
            foreach ($codeItems as $codeItem) {
                $codeItem->code = $codeSet->code_set_nm;
            }
            // return $response->ok($codeItems);
            $res = [
                'success' => true
            ];
            
            $res['data'] = $codeItems;
            $res['total'] = $total;
            return response()->withJson($res)
            ->withStatus(200);
        }
        return $response->ok([]);
    }
}
