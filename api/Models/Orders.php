<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 주문관리
 * BANK_DEPOSIT	은행계좌
 *     BANK_NM	은행명
 *     BANK_NUM	은행번호
 *     CANCEL_DATE	취소일자
 *     CARD_NUM	카트번호
 *     COPY_DATE	복사일자
 *     DELIVERY	배송방법
 *     DELIVERY_AMT	배송료
 *     DELIVERY_DATE	배송일자
 *     MEMO	메시지
 *     MEMO1	특이사항
 *     MEMO2	메모2
 *     ORDER_DATE	주문일자
 *     ORDER_NUM	주문번호
 *     PURPOSE	사용주체
 *     RECEIPT_AMT	금액
 *     RECEIPT_DATE	신청일자
 *     REPAY_DATE	환불일자
 *         STATUS	주문상태
 *     USEPO	사용목적

 * @property int $id 아이디(pk)
 * @property string $word_se 용어구분
 */
class Orders extends BaseModel
{
    use SoftDeletes;

    protected $table = 'orders';

    const DELETED_AT = 'delete_dt';

    protected $primaryKey = 'order_num';

    public $sort = 'order_num';

    public $sortable = ['order_num'];

    protected $fillable = [
        'bank_deposit',
        'bank_nm',
        'bank_num',
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
        'order_num',
        'purpose',
        'receipt_amt',
        'receipt_date',
        'repay_date',
        'status',
        'usepo'
    ];

    /**
     * 오더 아이템
     *
     * @return \Api\Models\OrderItem[]
     */
    public function orderItems()
    {
        return $this->hasMany(\Api\Models\OrderItem::class, 'order_num', 'order_num');
    }
    /**
     * 오더 커스텀
     *
     * @return \Api\Models\order_customer[]
     */
    public function order_customer()
    {
        return $this->hasOne(\Api\Models\OrderCustomers::class, 'order_num', 'order_num');
    }
}
