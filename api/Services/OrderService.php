<?php

namespace Api\Services;

use Api\Models\User;

use Api\Models\OrderCustomers;
use Api\Services\BaseService;
use Api\Models\Orders;



/**
 * 아카이브 관리 서비스
 */
class OrderService extends BaseService
{
    /**
     * 아카이브 관리 
     * 주문관리 목록
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */

    public function list()
    {
        $query = Orders::query()->with('order_customer');
        return $query;
    }
    /**
     * order 생성
     *
     * @param [type] $dto
     * @return void
     */
    public function create($dto, $orderNum, $user)
    {

        $orderArr = [
            'receipt_date_order',
            // 'bank_deposit_order',
            // 'bank_nm',
            // 'bank_num',
            'cancel_date',
            'card_num',
            'copy_date',
            'delivery',
            'delivery_amt',
            'delivery_date',
            'memo',
            'memo1',
            'memo2',
            'order_date',
            // 'order_num',
            'purpose',
            'receipt_amt',
            'repay_date',
            'status',
            'usepo'
        ];

        // 날짜형식 변환
        // $dto->receipt_date_order = date("Ymd", strtotime($dto->receipt_date));

        $receipts_amt = json_decode($dto->selData);
        $receipt_amt = 0;

        foreach ($receipts_amt as $price) {
            // $receipt_amt = $receipt_amt + $price->receipt_amt;
            $receipt_amt = $receipt_amt + $price->price;
        }

        $orderDto = $dto->only(...$orderArr);

        $order = new Orders();



        foreach ($orderDto->toArray() as $key => $val) {

            if ($key == 'bank_deposit_order') {

                $order->bank_deposit = $val;
            } else if ($key == 'receipt_date_order') {
                $order->receipt_date = $val;
            } else {
                $order->$key = $val;
            }
        }


        $order->receipt_amt = $receipt_amt;
        $order->order_num = $orderNum;
        // $order->receipt_date = date("Ymd");
        $order->regist_user_id = $user->user_id;
        $order->updt_user_id = $user->user_id;

        $order->save();
        return $order;
    }
    /**
     * order_num으로 order조회
     *
     * @param int $orderNum
     * @return void
     */
    public function getOrderbyOrderNum($orderNum)
    {
        $query = Orders::query();

        return $query->with('orderItems')->where('order_num', $orderNum);
    }
    /**
     * order_num으로 order조회 예외
     *
     * @param [type] $orderNum
     * @return void
     */
    public function getOrFail($orderNum)
    {
        $order = $this->getOrderbyOrderNum($orderNum);
        if (!$order) {
            api_abort_404('order');
        }
        return $order;
    }
    /**
     * order 수정
     *
     * @param [type] $dto
     * @param integer $orderNum
     * @return void
     */
    public function update($dto, int $orderNum, $user)
    {

        $receipts_amt = json_decode($dto->selData);
        $receipt_amt = 0;

        foreach ($receipts_amt as $price) {

            // $receipt_amt = $receipt_amt + $price->receipt_amt;
            $receipt_amt = $receipt_amt + $price->price;
        }
        $orderArr = [

            // 'bank_deposit_order',
            // 'bank_nm',
            // 'bank_num',
            // 'cancel_date',
            'card_num',
            'copy_date',
            'delivery',
            'delivery_amt',
            // 'delivery_date',
            'memo',
            'memo1',
            'memo2',
            // 'order_date',
            // 'order_num',
            'purpose',
            'receipt_amt',

            'receipt_date_order',
            'repay_date',
            // 'status',
            'usepo'
        ];

        // 날짜형식 변환
        // $dto->receipt_date = date("Ymd", strtotime($dto->receipt_date_order));



        $orderDto = $dto->only(...$orderArr);

        $order = $this->getOrFail($orderNum)->first();


        foreach ($orderDto->toArray() as $key => $val) {
            if ($key == 'bank_deposit_order') {

                $order->bank_deposit = $val;
            } else if ($key == 'receipt_date_order') {

                $order->receipt_date = $val;
            } else {

                $order->$key = $val;
            }
        }
        $order->regist_user_id = $user->user_id;
        $order->updt_user_id = $user->user_id;
        $order->receipt_amt = $receipt_amt;
        $order->save();

        return $order;
    }
    /**
     * order삭제
     *
     * @param integer $orderNum
     * @return void
     */
    public function delete(int $orderNum)
    {
        $order = $this->getOrFail($orderNum);
        $ret = $order->delete();
        return $ret;
    }
    /**
     * 주문 상태 업데이트
     *
     * @param string $orderNum
     * @param string $changeStatus
     * @return \Api\Models\Orders
     */
    public function statusUpdate($orderNum, $changeStatus)
    {
        $orders = $this->getOrFail($orderNum);
        $order = $orders->first();




        /**
         * 1,접수처리 2,자료복사 3,자료배송 4,판매완료 5,주문취소 6,환불및반품
         */

        //  복사 취소일
        if ($changeStatus == '1') {
            $order->copy_date = null;
        }


        // 자료 복사
        if ($changeStatus == '8') {

            // 복사일
            $order->copy_date = date("Ymd");
            // 배송 취소일
            $order->delivery_date = null;
        }
        // 자료 배송
        if ($changeStatus == '3') {
            // 배송일

            $order->delivery_date = date("Ymd");
        }
        // 주문 취소
        if ($changeStatus == '5') {
            // 취소일

            $order->cancel_date = date("Ymd");
        }

        $order->status = $changeStatus;
        $order->save();

        return $order;
    }
}
