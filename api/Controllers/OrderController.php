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
use Api\Services\ContentService;
use Api\Models\Orders;
use Api\Types\ReceiptStatus;


class OrderController extends BaseController
{

    /**
     * 주문 서비스
     *
     * @var \Api\Services\OrderService
     */
    private $orderService;

    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->orderService = new OrderService($container);
    }
    /**
     * 주문관리 목록
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function index(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();

        /**
         * 주문관리 검색 parma 
         * $start_date 신청일 시작일
         * $end_date  신청일 마지막일
         * $search_order_num 주문번호
         * $searchNm 이름
         * $status 주문상태
         *  $receipt  입금상태 [입금전,입금후]
         */
        $param = new archiveManagementSearchOrderParam($input);

        $start_date = $param->start_date;
        $end_date = $param->end_date;
        $search_order_num = $param->search_order_num;
        $searchNm = $param->search_cust_nm;
        $status = $param->status;
        $receipt = $param->receipt;


        /**
         * 서비스
         */
        $orderCustomerService = new OrderCustomerService($this->container);
        $orderItemsService = new OrderItemService($this->container);
        $contentService = new ContentService($this->container);


        $orderQuery = $this->orderService->list();



        // 코드 아이템 가져오기
        $dataDicCodeSetService = new DataDicCodeSetService($this->container);
        $selectCodeFields = ['id', 'code_itm_code', 'code_itm_nm'];
        $sysCodeItems = $dataDicCodeSetService->findByCodeOrFail('*OR001')
            ->codeItems()
            ->get($selectCodeFields);


        /**
         * 주문번호 검색
         */
        if ((!is_null($search_order_num)) && !empty($search_order_num)) {

            $orderQuery->where('order_num', $search_order_num);
        };
        /**
         * 이름으로 검색
         */
        if (!is_null($searchNm)  && !empty($searchNm)) {
            $orderNums = $orderCustomerService->getOrderNumByCustNm($searchNm);

            if (!is_null($orderNums->first())) {
                foreach ($orderNums as $orderNum) {

                    $getOrderNum = $orderNum->order_num;
                    $orderQuery->where('order_num', $getOrderNum);
                }
            } else {
                $orderQuery->where('order_num', null);
            }
        };
        /**
         * 진행상태 검색
         */
        if ((!is_null($status)) && !empty($status)) {

            $orderQuery->where('status', $status);
        };

        /**
         * 입금상태 검색
         */
        if (!is_null($receipt) && !empty($receipt)) {
            switch ($receipt) {
                case ReceiptStatus::TOTAL:
                    break;
                case ReceiptStatus::BEFORE:
                    $orderQuery->whereNull('receipt_date');
                    break;
                case ReceiptStatus::AFTER:
                    $orderQuery->whereNotNull('receipt_date');
                    break;
            }
        }

        /**
         * 신청일 검색
         */
        $orderQuery->whereBetween('order_date', [$start_date, $end_date])->orderBy('regist_dt', 'desc');

        $orders = paginate($orderQuery);

        foreach ($orders as $order) {
            // foreach ($orders as $order) {

            $orderNum = $order->order_num;
            $orderStatus = $order->status;

            // $order->order_date = (new \Carbon\Carbon($order->order_date))->format('Y-m-d');
            $order->order_date = dateToStr($order->order_date);
            is_null($order->receipt_date) ?: $order->receipt_date = dateToStr($order->receipt_date);





            // $order_customer = $orderCustomerService->getOrderNumByOrderCustomers($orderNum);
            // $order_customer->receipt_date = dateToStr($order_customer->receipt_date);

            // $orderItems = $orderItemsService->getOrderNumByOrderItems($orderNum);


            // $contentIdArr = [];
            // foreach ($orderItems as $key => $orderItem) {
            //     $contentId = $orderItem->content_id;
            //     $content = $contentService->getContentByContentId($contentId);
            //     $contentIdArr[] = $content;
            // }


            // $order->order_customer = $order_customer;

            // $order->content = $contentIdArr;
            $order->orderBD = DataDicCodeItemService::getCodeItemByCode($sysCodeItems, $orderStatus);
        }
        // return $response->ok(paginate($orders));
        // return $response->ok($orders);

        return $response->ok($orders);
    }
    /**
     * 주문관리 등록
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function create(ApiRequest $request, ApiResponse $response, array $args)
    {
        /**
         * date Ymdhis format으로 order_num 등록
         */
        $orderNum = date('Ymdhis');
        $user = auth()->user();
        $data = $request->all();

        /**
         * 서비스
         */
        $orderCustomerService = new OrderCustomerService($this->container);
        $orderItemService = new orderItemService($this->container);

        /**
         * param
         */
        $dto = new archiveManagementOrderDto($data);
        // 추가된 코드 아이템 목록

        $orderItems = json_decode($dto->selData);

        /**
         * order ,order_customrs  입금일 컬럼명이 같아서 
         * order는 receipt_date_order 로 처리
         */
        $dto->receipt_date = date("Ymd", strtotime($dto->receipt_date));
        if ($dto->receipt_date_order == null) {
            $dto->receipt_date_order = null;
        } else {
            $dto->receipt_date_order = date("Ymd", strtotime($dto->receipt_date_order));
        };

        // 주문내역 등록
        $order = $this->orderService->create($dto, $orderNum, $user);
        // 주문자 배송내역 등록
        $orderCustomer = $orderCustomerService->create($dto, $orderNum, $user);
        // 주문상세내역 등록
        foreach ($orderItems as $item) {
            $ordItems = $orderItemService->create($item, $orderNum, $user);
        }

        $order->orderCustomer = $orderCustomer;
        $order->orderItems = $ordItems;

        return $response->ok($order, 201);
    }
    /**
     * 주문관리 수정
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function update(ApiRequest $request, ApiResponse $response, array $args)
    {
        $orderNum = $args['order_num'];
        $user = auth()->user();
        $data = $request->all();

        /**
         * param
         */
        $dto = new archiveManagementOrderDto($data);
        // date type "Ymd" format
        $dto->receipt_date = date("Ymd", strtotime($dto->receipt_date));

        /**
         * order ,order_customrs  입금일 컬럼명이 같아서 
         * order는 receipt_date_order 로 처리
         */
        if ($dto->receipt_date_order == null) {
            $dto->receipt_date_order = null;
        } else {
            $dto->receipt_date_order = date("Ymd", strtotime($dto->receipt_date_order));
        };

        // 추가 변경된 주문 상세내역
        $orderItems = json_decode($dto->selData);

        /**
         * 서비스
         */
        $orderCustomerService = new OrderCustomerService($this->container);
        $orderItemService = new orderItemService($this->container);

        $this->orderService->update($dto, $orderNum, $user);
        $orderCustomerService->update($dto, $orderNum, $user);


        /**
         * order_num 으로 기존에 있던 아이템 
         */
        $originOrderItems = $orderItemService->getOrFail($orderNum)->get();
        $originOrderItemArr = [];
        foreach ($originOrderItems as $originOrderItem) {
            $originOrderItemArr[] = $originOrderItem->idx;
        }
        /**
         * 기존 데이터와 비교후 수정 또는 삭제
         */
        $deleteOrderItemsArr = [];
        foreach ($orderItems as $orderItem) {
            if (!is_null($orderItem->idx)) {
                // $orderItemService->update($orderItem, $orderNum);
                $orderItemService->update($orderItem, $orderNum, $user);
            } else {
                $orderItemService->create($orderItem, $orderNum, $user);
            }
            $deleteOrderItemsArr[] = $orderItem->idx;
        }

        $deleteIdxArr = array_diff($originOrderItemArr, $deleteOrderItemsArr);
        foreach ($deleteIdxArr as $deleteIdx) {
            $orderItemService->deleteIdx($deleteIdx);
        }
    }

    // 진행상태 변경
    public function statusUpdate(ApiRequest $request, ApiResponse $response, array $args)
    {

        // param
        $data = $request->all();
        $changeStatus = $data['status'];
        // $toggle = $data['toggle'];
        $orderNum = $args['order_num'];

        // // status 가 자료복사(2) 이면 order_item의 변환상태(status)를 변환중으로 바꾼다
        // if ($changeStatus == '2') {

        //     $orderItemService = new OrderItemService($this->container);
        //     $orderItemService->statusChange($orderNum);
        // }
        /**
         * order 진행상태 변경 서비스
         */
        $changeStatus = $this->orderService->statusUpdate($orderNum, $changeStatus);

        // $changeStatusCode = $changeStatus->status;

        // // 코드 아이템 가져오기
        // $dataDicCodeSetService = new DataDicCodeSetService($this->container);
        // $selectCodeFields = ['id', 'code_itm_code', 'code_itm_nm'];
        // $statusCodeItem = $dataDicCodeSetService->findByCodeOrFail('*OR001')
        //     ->codeItems()
        //     ->where('code_itm_code', $changeStatusCode)->first($selectCodeFields);



        // $changeStatus->code = $statusCodeItem;

        return $response->ok($changeStatus);
    }



    /**
     * 주문관리 삭제
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function delete(ApiRequest $request, ApiResponse $response, array $args)
    {
        $orderNum = $args['order_num'];
        /**
         * 서비스
         */
        $orderCustomerService = new OrderCustomerService($this->container);
        $orderItemService = new orderItemService($this->container);

        $this->orderService->delete($orderNum);
        $orderCustomerService->delete($orderNum);
        $orderItemService->delete($orderNum);

        return $response->ok();
    }
}
