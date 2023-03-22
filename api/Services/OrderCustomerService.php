<?php

namespace Api\Services;

use Api\Models\User;

use Api\Models\OrderCustomers;
use Api\Services\BaseService;
use Api\Models\OrdersCustomers;
use Api\Models\OrderItem;



/**
 * 아카이브 관리 서비스
 */
class OrderCustomerService extends BaseService
{
    /**
     * orders 의 ordernum 으로 orderCustomers 연결
     *
     * @param integer $orderNum
     * @return void
     */
    public function getOrderNumByOrderCustomers(int $orderNum)
    {
        $OrderCustomers = OrderCustomers::get()->where('order_num', $orderNum)->first();
        return $OrderCustomers;
    }

    /**
     * 이름을 검색
     *
     * @param string $custNm
     * @return void
     */
    public function getOrderNumByCustNm(string $custNm)
    {
        $OrderCustomers = OrderCustomers::get()->where('cust_nm', $custNm);

        return $OrderCustomers;
    }



    /**
     * Undocumented function
     *
     * @param [type] $dto
     * @return void
     */
    public function create($dto, $orderNum, $user)
    {

        $orderCustomerArr = [
            'address1',
            'address2',
            'bank_deposit',
            'cust_bank_deposit',
            'bank_nm',
            'bank_num',
            'cust_nm',
            'email',
            // 'order_num',
            'phone',
            'receipt_date',
            'zipcode'
        ];

        // 날짜형식 변환
        $dto->receipt_date = date("Ymd", strtotime($dto->receipt_date));


        $orderDto = $dto->only(...$orderCustomerArr);
        $orderCustomers = new orderCustomers();
        foreach ($orderDto->toArray() as $key => $val) {
            $orderCustomers->$key = $val;
        }
        $orderCustomers->order_num =  $orderNum;
        $orderCustomers->bank_nm = '신한은행';
        $orderCustomers->bank_num = '389-01-131011';
        $orderCustomers->regist_user_id = $user->user_id;
        $orderCustomers->updt_user_id = $user->user_id;
        $orderCustomers->save();

        return $orderCustomers;
    }

    public function getOrderCustomersbyOrderNum($orderNum)
    {
        $query = OrderCustomers::query();

        return $query->where('order_num', $orderNum);
    }

    public function getOrFail($orderNum)
    {
        $order = $this->getOrderCustomersbyOrderNum($orderNum);
        if (!$order) {
            api_abort_404('order_customer');
        }
        return $order;
    }

    public function update($dto, int $orderNum, $user)
    {
        $orderCustomerArr = [
            'address1',
            'address2',
            'bank_deposit',
            'cust_bank_deposit',

            'cust_nm',
            'email',
            // 'order_num',
            'phone',
            'receipt_date',
            'zipcode'
        ];

        // 날짜형식 변환
        $dto->receipt_date = date("Ymd", strtotime($dto->receipt_date));


        $orderDto = $dto->only(...$orderCustomerArr);

        $orderCustomers = $this->getOrFail($orderNum)->first();

        foreach ($orderDto->toArray() as $key => $val) {
            $orderCustomers->$key = $val;
        }
        $orderCustomers->bank_nm = '신한은행';
        $orderCustomers->bank_num = '389-01-131011';
        $orderCustomers->regist_user_id = $user->user_id;
        $orderCustomers->updt_user_id = $user->user_id;
        $orderCustomers->save();
        return $orderCustomers;
    }

    public function delete(int $orderNum)
    {
        $order = $this->getOrFail($orderNum);
        $ret = $order->delete();
        return $ret;
    }
}
