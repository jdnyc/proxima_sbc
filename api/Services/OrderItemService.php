<?php

namespace Api\Services;

use Api\Models\User;

use Api\Models\OrderCustomers;
use Api\Services\BaseService;
use Api\Models\OrderItem;



/**
 * 아카이브 관리 서비스
 */
class OrderItemService extends BaseService
{





    /**
     * 아카이브 관리 
     * orderItem 리스트
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */

    public function list()
    {
        $query = OrderItem::query();
        return $query;
    }
    /**
     * orders 의 ordernum 으로 orderItem 연결
     *
     * @param integer $orderNum
     * @return void
     */
    public function getOrderNumByOrderItems(int $orderNum)
    {
        $OrderItems = OrderItem::where('order_num', $orderNum)->get();
        return $OrderItems;
    }

    /**
     * Undocumented function
     *
     * @param [type] $dto
     * @return void
     */
    public function create($dto, $orderNum, $user)
    {


        $orderItem = new OrderItem();

        $orderItem->order_num = $orderNum;
        $orderItem->idx = date('his') + $dto->content_id;

        $orderItem->content_id = $dto->content_id;

        $orderItem->amount = $dto->amount;
        $orderItem->datanum = $dto->datanum;
        $orderItem->homepage_key = $dto->homepage_key;

        $orderItem->method = $dto->method;
        $orderItem->price = $dto->price;
        $orderItem->progdate = $dto->progdate;
        $orderItem->proglength = $dto->proglength;
        $orderItem->prognm = $dto->prognm;
        $orderItem->prognum = $dto->prognum;
        $orderItem->progsection = $dto->progsection;
        $orderItem->progtitle = $dto->progtitle;
        $orderItem->prolength = $dto->proglength;
        $orderItem->price = $dto->receipt_amt;
        $orderItem->tc_in = $dto->tc_in;
        $orderItem->tc_out = $dto->tc_out;
        $orderItem->regist_user_id = $user->user_id;
        $orderItem->updt_user_id = $user->user_id;
        $orderItem->status = $dto->status;

        $orderItem->save();
        return $orderItem;
    }

    public function update($dto, int $orderNum, $user)
    {
        $contentId = $dto->content_id;

        $orderItemIdx = $dto->idx;

        $orderItem = $this->getOrderItemByOrderNumAndContentId($orderNum, $orderItemIdx)->find($orderItemIdx);

        $orderItem->amount = $dto->amount;
        $orderItem->datanum = $dto->datanum;
        $orderItem->homepage_key = $dto->homepage_key;
        $orderItem->idx = $dto->idx;
        $orderItem->method = $dto->method;
        $orderItem->price = $dto->price;
        $orderItem->progdate = $dto->progdate;
        $orderItem->proglength = $dto->proglength;
        $orderItem->prognm = $dto->prognm;
        $orderItem->prognum = $dto->prognum;
        $orderItem->progsection = $dto->progsection;
        $orderItem->progtitle = $dto->progtitle;
        $orderItem->prolength = $dto->proglength;
        $orderItem->price = $dto->receipt_amt;
        $orderItem->tc_in = $dto->tc_in;
        $orderItem->tc_out = $dto->tc_out;
        $orderItem->regist_user_id = $user->user_id;
        $orderItem->updt_user_id = $user->user_id;
        $orderItem->save();
        return $orderItem;
    }


    public function getOrderItembyOrderNum($orderNum)
    {
        $query = OrderItem::query();

        return $query->where('order_num', $orderNum);
    }
    public function getOrFail($orderNum)
    {
        $order = $this->getOrderItembyOrderNum($orderNum);
        if (!$order) {
            api_abort_404('orderItem');
        }
        return $order;
    }


    public function getOrderItembyIdx($idx)
    {
        $query = OrderItem::query();

        return $query->where('idx', $idx);
    }




    // public function update($orderItem, int $orderNum)
    // {



    //     dd($orderNum);
    //     $getOrderItem = $this->getOrFail($orderNum)->first();
    //     dd($getOrderItem);


    //     $orderItem->save();
    //     return $orderItem;
    // }

    /**
     * orderNum으로 삭제 
     *
     * @param integer $orderNum
     * @return void
     */
    public function delete(int $orderNum)
    {
        $orderItems = $this->getOrFail($orderNum);
        $ret = $orderItems->delete();
        return $ret;
    }
    /**
     * idx 삭제 (고유값)
     *
     * @param integer $orderNum
     * @return void
     */
    public function deleteIdx(int $idx)
    {
        $orderItem = $this->getOrderItembyIdx($idx);

        $ret = $orderItem->delete();
        return $ret;
    }
    /**
     * orderNUM 과 content_id 로 값 찾기
     *
     * @param integer $orderNum
     * @param integer $contentId
     * @return void
     */
    public function getOrderItemByOrderNumAndContentId(int $orderNum, $idx)
    {
        $orderItems = $this->getOrFail($orderNum);
        $orderItem = $orderItems->where('idx', $idx);

        return $orderItem;
    }

    /**
     * status 상태를 대기에서 변환중으로 바꾼다
     * order_num으로 아이템 조회후 상태 변환
     *
     * @param integer $orderNum
     * @return void
     */
    public function statusChange(int $orderNum)
    {
        $orderItems = $this->getOrderItembyOrderNum($orderNum)->get();

        foreach ($orderItems as $orderItem) {

            $orderItem->status = "변환중";
            $orderItem->save();
        }
    }
}
