<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 주문자 정보
 * ADDRESS1	주소1
 * ADDRESS2	주소2
 * BANK_DEPOSIT	은행계좌
 * CUST_NM	주문자명
 * EMAIL	이메일
 * ORDER_NUM	주문번호
 * PHONE	전화번호
 * RECEIPT_DATE	신청일자
 * ZIPCODE	우편번호

 */
class OrderCustomers extends BaseModel
{
    use SoftDeletes;

    protected $table = 'order_customers';

    const DELETED_AT = 'delete_dt';

    protected $primaryKey = 'order_num';

    public $sortable = ['order_num'];

    protected $fillable = [
        'address1',
        'address2',
        'bank_deposit',
        'cust_nm',
        'email',
        'order_num',
        'phone',
        'receipt_date',
        'zipcode'
    ];
}
