<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Psr\Container\ContainerInterface;
use Api\Services\DTOs\archiveManagementSearchOrderItemParam;
use Api\Services\OrderItemService;
use Api\Services\OrderSalesPriceService;

class OrderItemController extends BaseController
{

    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->orderItemService = new OrderItemService($container);
    }
    /**
     * 주문관리 리스트
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function index(ApiRequest $request, ApiResponse $response, array $args)
    {

        $input = $request->all();
        $param = new archiveManagementSearchOrderItemParam($input);
        $keyword = $param->keyword;

        $orderItemService = $this->orderItemService->list();

        if (is_null($keyword) || empty($keyword)) {

            $orderItems = $orderItemService->get();
        } else {

            $orderItems = $orderItemService->where('prognm', 'like', "%{$keyword}%")
                ->orWhere('title', 'like', "%{$keyword}%")
                ->get();
        }

        return $response->ok($orderItems);
    }


    //order_num으로 조회
    public function show(ApiRequest $request, ApiResponse $response, array $args)
    {
        $orderNum = $args['order_num'];

        // order_num으로 order_item조회
        $orderItems = $this->orderItemService->getOrderNumByOrderItems($orderNum);

        /**
         * 서비스
         */
        $orderSalesPriceService = new OrderSalesPriceService($this->container);

        // foreach ($orderItems as $orderItem) {
        //     // method별 가격정보들
        //     $prices = $orderSalesPriceService->list()->get(['method', 'prolength', 'price']);
        //     $orderItem->prices = $prices;
        // };
        $prices = $orderSalesPriceService->list()->get(['method', 'prolength', 'price']);
        // $orderItemsArr = [];
        // $orderItemsArr['orderItems'] = $orderItems;
        // $orderItemsArr['priceData'] = $prices;

        return $response->ok($orderItems);
    }
    /**
     * 오더아이템 컨텐츠  삭제
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function delete(ApiRequest $request, ApiResponse $response, array $args)
    {
        $orderNum = $args['order_num'];

        $input = $request->all();

        $deleteContentIdArr = json_decode($input['checkDeleteContetntId']);

        $orderItems = $this->orderItemService->getOrderNumByOrderItems($orderNum);

        foreach ($orderItems as $orderItem) {
            $orderItemContentId = $orderItem->content_id;
            foreach ($deleteContentIdArr as $deleteContentId) {
                if ($orderItemContentId == $deleteContentId) {
                    $orderItemIdx = $orderItem->idx;

                    $this->orderItemService->deleteIdx($orderItemIdx);
                }
            }
        }
        return $response->ok();
    }
}
