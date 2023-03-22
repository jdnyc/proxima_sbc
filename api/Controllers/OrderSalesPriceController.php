<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Psr\Container\ContainerInterface;
use Api\Services\OrderCustomerService;
use Api\Services\OrderService;
use Api\Services\OrderItemService;
use Api\Services\DTOs\archiveManagementOrderDto;
use Api\Services\DTOs\archiveManagementSearchOrderParam;
use Api\Services\DataDicCodeSetService;
use Api\Services\DataDicCodeItemService;
use Api\Services\OrderSalesPriceService;
use Api\Models\OrderSalesPrice;

class OrderSalesPriceController extends BaseController
{
    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->orderSalesPrice = new OrderSalesPriceService($container);
    }
    /**
     *가격 관리 목록
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function index(ApiRequest $request, ApiResponse $response, array $args)
    {
        $orderSalesPriceLists = $this->orderSalesPrice->list();


        return $response->ok($orderSalesPriceLists->orderBy('idx', 'asc')->get());
    }

    // public function getPriceFieldByMethod(ApiRequest $request, ApiResponse $response, array $args)
    // {
    //     $method = $args['method'];
    //     $orderSalesPriceLists = $this->orderSalesPrice->getOrderSalesPriceListByMethod($method);
    //     return $response->ok($orderSalesPriceLists->get());
    // }

    /**
     * 가격관리 이미 있는 레코드 들은 수정, 새로 추가된 레코드는 생성
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function update(ApiRequest $request, ApiResponse $response, array $args)
    {
        $data = $request->all();

        $records = json_decode($data['record']);

        foreach ($records as $record) {
            $recordIdx = $record->idx;

            $updateOrCreateCheck = $this->orderSalesPrice->findIfNullReturnFalse($recordIdx);

            if ($updateOrCreateCheck) {

                $this->orderSalesPrice->update($record);
            } else {
                $this->orderSalesPrice->create($record);
            }
        };
    }

    public function delete(ApiRequest $request, ApiResponse $response, array $args)
    {
        $idx = $args['idx'];
        $this->orderSalesPrice->delete($idx);
        return $response->ok();
    }
}
