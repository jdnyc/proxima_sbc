<?php

namespace Api\Services;

use Api\Models\User;

use Api\Models\OrderCustomers;
use Api\Services\BaseService;
use Api\Models\OrderSalesPrice;



/**
 * 아카이브 관리 서비스
 */
class OrderSalesPriceService extends BaseService
{
    /**
     * 아카이브 관리 
     * 주문관리 목록
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */

    public function list()
    {
        $query = OrderSalesPrice::query();
        /**
         * 지운목록
         */
        // ->withTrashed()
        return $query;
    }

    public function getMaxIdx()
    {
        $query = OrderSalesPrice::query()->withTrashed()->max('idx');
        // $idxs = $query->withTrashed()->get('idx');
        $maxIdx = $query + 1;
        // return $maxIdx;
        // return $query;
        return  $maxIdx;
    }


    public function getOrderSalesPriceListByMethod($method)
    {
        $query = OrderSalesPrice::query();
        $query->where('method', $method);;

        return $query;
    }

    public function findIfNullReturnFalse($id)
    {
        $query = OrderSalesPrice::query();
        $orderSalesPrice = $query->find($id);
        // $orderSalesPrice = $this->getMaxIdx();
        if (!$orderSalesPrice) {
            return false;
        } else {
            return $orderSalesPrice;
        }
    }

    public function find($id)
    {
        $query = OrderSalesPrice::query();

        return $query->find($id);
    }

    public function update($record)
    {
        $recordIdx = $record->idx;
        $orderSalesPrice = $this->find($recordIdx);
        $orderSalesPrice->method = $record->method;
        $orderSalesPrice->prolength = $record->prolength;
        $orderSalesPrice->price = $record->price;
        $orderSalesPrice->won_price = $record->won_price;
        $orderSalesPrice->save();
        return  $orderSalesPrice;
    }


    public function create($record)
    {

        $orderSalesPrice = new OrderSalesPrice();
        // $orderSalesPrice->idx = $record->idx;
        $orderSalesPrice->idx = $this->getMaxIdx();
        $orderSalesPrice->method = $record->method;
        $orderSalesPrice->prolength = $record->prolength;
        $orderSalesPrice->price = $record->price;
        $orderSalesPrice->won_price = $record->won_price;
        $orderSalesPrice->save();
        return  $orderSalesPrice;
    }

    public function delete($idx)
    {
        $orderSalesPrice = $this->find($idx);
        $ret =  $orderSalesPrice->delete();
        return $ret;
    }
}
